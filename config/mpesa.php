<?php
function mpesa_setting(string $key,string $default=''):string{
 global $conn;
 if(isset($conn)&&$conn instanceof mysqli){
  $stmt=$conn->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
  if($stmt){$stmt->bind_param('s',$key);$stmt->execute();$value=$stmt->get_result()->fetch_assoc()['setting_value']??null;$stmt->close();if($value!==null&&$value!=='')return(string)$value;}
 }
 $envMap=['mpesa_environment'=>'MPESA_ENV','mpesa_consumer_key'=>'MPESA_CONSUMER_KEY','mpesa_consumer_secret'=>'MPESA_CONSUMER_SECRET','mpesa_shortcode'=>'MPESA_SHORTCODE','mpesa_passkey'=>'MPESA_PASSKEY','mpesa_callback_url'=>'MPESA_CALLBACK_URL','mpesa_account_reference'=>'MPESA_ACCOUNT_REFERENCE'];
 $env=$envMap[$key]??'';$value=$env!==''?getenv($env):false;return($value!==false&&$value!=='')?(string)$value:$default;
}
function mpesa_base_url():string{return strtolower(mpesa_setting('mpesa_environment','sandbox'))==='live'?'https://api.safaricom.co.ke':'https://sandbox.safaricom.co.ke';}
function mpesa_http_json(string $url,string $method,?string $body=null,array $headers=[]):array{
 $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>45,CURLOPT_CONNECTTIMEOUT=>15]);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
 $response=curl_exec($ch);$httpCode=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
 if($response===false)throw new Exception('M-Pesa connection failed: '.$error);
 $data=json_decode($response,true);if(!is_array($data))throw new Exception('Invalid M-Pesa response.');
 if($httpCode>=400)throw new Exception($data['errorMessage']??$data['error_description']??'M-Pesa API request failed.');
 return$data;
}
function mpesa_access_token():string{
 $key=mpesa_setting('mpesa_consumer_key');$secret=mpesa_setting('mpesa_consumer_secret');
 if($key===''||$secret==='')throw new Exception('M-Pesa Consumer Key and Consumer Secret are not configured.');
 $data=mpesa_http_json(mpesa_base_url().'/oauth/v1/generate?grant_type=client_credentials','GET',null,['Authorization: Basic '.base64_encode($key.':'.$secret),'Content-Type: application/json']);
 if(empty($data['access_token']))throw new Exception('M-Pesa access token was not returned.');return(string)$data['access_token'];
}
function mpesa_normalize_phone(string $phone):string{
 $phone=preg_replace('/\D+/','',$phone)??'';
 if(preg_match('/^0?7\d{8}$/',$phone))return'254'.substr($phone,-9);
 if(preg_match('/^2547\d{8}$/',$phone))return$phone;
 throw new Exception('Enter a valid Kenyan M-Pesa phone number.');
}
function mpesa_initiate_stk($conn,int $invoiceId,int $patientId,float $amount,string $phone,int $cashierShiftId=0):array{
 if($invoiceId<=0)throw new Exception('Invalid invoice.');
 $inv=$conn->prepare("SELECT total FROM invoices WHERE id=? LIMIT 1 FOR UPDATE");
 if(!$inv)throw new Exception('Unable to load invoice before M-Pesa payment.');
 $inv->bind_param('i',$invoiceId);$inv->execute();$invoice=$inv->get_result()->fetch_assoc();$inv->close();
 if(!$invoice)throw new Exception('Invoice not found.');
 $item=$conn->prepare("SELECT COALESCE(SUM(total),0) total FROM invoice_items WHERE invoice_id=?");
 $itemTotal=0.0;if($item){$item->bind_param('i',$invoiceId);$item->execute();$itemTotal=(float)($item->get_result()->fetch_assoc()['total']??0);$item->close();}
 $total=$itemTotal>0?$itemTotal:(float)$invoice['total'];
 $paidStmt=$conn->prepare("SELECT COALESCE(SUM(amount),0)-COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.invoice_id=p.invoice_id AND r.status='Approved'),0) paid FROM payments p WHERE p.invoice_id=?");
 $paid=0.0;if($paidStmt){$paidStmt->bind_param('i',$invoiceId);$paidStmt->execute();$paid=(float)($paidStmt->get_result()->fetch_assoc()['paid']??0);$paidStmt->close();}
 $balance=max($total-$paid,0);
 $amount=round($amount,2);if($amount<=0||$amount>$balance+0.00001)throw new Exception('M-Pesa amount exceeds the invoice balance.');
 if($cashierShiftId<=0)throw new Exception('An open cashier shift is required before starting an M-Pesa collection.');
 $currentCashierId=(int)($_SESSION['user_id'] ?? 0);
 if($currentCashierId<=0)throw new Exception('A logged-in cashier is required for M-Pesa collection.');
 $shift=$conn->prepare("SELECT id FROM cashier_shifts WHERE id=? AND cashier_id=? AND status='Open' LIMIT 1");
 if(!$shift)throw new Exception('Unable to verify cashier shift.');$shift->bind_param('ii',$cashierShiftId,$currentCashierId);$shift->execute();$ok=$shift->get_result()->fetch_assoc();$shift->close();if(!$ok)throw new Exception('The selected cashier shift is not open.');

 $shortcode=mpesa_setting('mpesa_shortcode');$passkey=mpesa_setting('mpesa_passkey');$callback=mpesa_setting('mpesa_callback_url');$accountReference=mpesa_setting('mpesa_account_reference','HMS');
 if($shortcode===''||$passkey===''||$callback==='')throw new Exception('Configure M-Pesa Shortcode, Passkey and Callback URL in System Settings first.');
 $amountInt=(int)round($amount);if($amountInt<1)throw new Exception('M-Pesa amount must be at least KES 1.');
 $phone=mpesa_normalize_phone($phone);$timestamp=date('YmdHis');$password=base64_encode($shortcode.$passkey.$timestamp);$token=mpesa_access_token();
 $payload=['BusinessShortCode'=>$shortcode,'Password'=>$password,'Timestamp'=>$timestamp,'TransactionType'=>'CustomerPayBillOnline','Amount'=>$amountInt,'PartyA'=>$phone,'PartyB'=>$shortcode,'PhoneNumber'=>$phone,'CallBackURL'=>$callback,'AccountReference'=>substr($accountReference.'-INV-'.$invoiceId,0,12),'TransactionDesc'=>substr('Hospital payment INV-'.$invoiceId,0,20)];
 $response=mpesa_http_json(mpesa_base_url().'/mpesa/stkpush/v1/processrequest','POST',json_encode($payload),['Authorization: Bearer '.$token,'Content-Type: application/json']);
 $stmt=$conn->prepare("INSERT INTO mpesa_transactions (invoice_id,patient_id,cashier_shift_id,amount,phone,merchant_request_id,checkout_request_id,result_code,result_desc,status,raw_response,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,'pending',?,NOW(),NOW())");
 if(!$stmt)throw new Exception('Unable to save M-Pesa transaction. Run mpesa_migration.sql first.');
 $merchant=$response['MerchantRequestID']??null;$checkout=$response['CheckoutRequestID']??null;$resultCode=isset($response['ResponseCode'])?(string)$response['ResponseCode']:null;$resultDesc=$response['ResponseDescription']??null;$raw=json_encode($response);
 $stmt->bind_param('iiidssssss',$invoiceId,$patientId,$cashierShiftId,$amountInt,$phone,$merchant,$checkout,$resultCode,$resultDesc,$raw);
 if(!$stmt->execute()){$error=$stmt->error;$stmt->close();throw new Exception('Unable to save M-Pesa transaction: '.$error);} $stmt->close();return$response;
}
