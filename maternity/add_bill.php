<?php
require_once __DIR__ . '/../config/config.php';require_once __DIR__ . '/../includes/session.php';require_once __DIR__ . '/../includes/auth.php';require_once __DIR__ . '/../helpers/billing.php';require_login();require_module_access($conn,'finance','create');require_role(['admin','cashier','accountant']);
$id=max(0,(int)($_GET['id']??$_POST['maternity_id']??0));if(!$id){header('Location:index.php');exit;}
$s=$conn->prepare("SELECT m.patient_id,p.full_name,m.anc_number FROM maternity m JOIN patients p ON p.id=m.patient_id WHERE m.id=? LIMIT 1");$s->bind_param('i',$id);$s->execute();$m=$s->get_result()->fetch_assoc();$s->close();if(!$m){http_response_code(404);exit('Maternity record not found.');}
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));$csrfToken=$_SESSION['csrf_token'];$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrfToken,(string)($_POST['csrf_token']??''))){
        $error='Security token mismatch.';
    }else{
        $serviceId=max(0,(int)($_POST['service_id']??0));
        if($serviceId<=0){
            $error='Select a maternity service from the Service Catalogue.';
        }else{
            $serviceStmt=$conn->prepare("SELECT id,service_name,category,department FROM services_master WHERE id=? AND active=1 AND (category='maternity' OR LOWER(department)='maternity') LIMIT 1");
            if(!$serviceStmt){$error='Unable to load maternity service.';}
            else{
                $serviceStmt->bind_param('i',$serviceId);$serviceStmt->execute();$service=$serviceStmt->get_result()->fetch_assoc();$serviceStmt->close();
                if(!$service){$error='Selected service is not an active Maternity catalogue service.';}
                else{
                    $conn->begin_transaction();
                    try{
                        $patientId=(int)$m['patient_id'];
                        $visitId=get_or_create_current_visit($conn,$patientId,'Outpatient','Maternity');
                        $resolved=get_service_price_for_patient($conn,$patientId,$serviceId);
                        $amount=(float)$resolved['price'];
                        if (!empty($resolved['billable'])) {
                            $invoiceId=get_or_create_visit_invoice($conn,$patientId,$visitId);
                            $itemId=add_invoice_item($conn,$invoiceId,'Maternity Service: '.$resolved['service_name'],1,$amount,'maternity',$serviceId);
                            post_invoice_journal($conn,$invoiceId,$patientId,$amount,'Maternity catalogue service',$itemId);
                        }
                        if(function_exists('audit'))audit('maternity_charge',"maternity_id={$id},service_id={$serviceId},amount={$amount}");
                        $conn->commit();header("Location:view.php?id={$id}&charge=1");exit;
                    }catch(Throwable $e){$conn->rollback();$error=$e->getMessage();}
                }
            }
        }
    }
}
include __DIR__ . '/../includes/header.php';include __DIR__ . '/../includes/sidebar.php';?>
<div class="main-content"><div class="container-fluid pt-4"><div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h4 mb-1 text-gray-800"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Add Maternity Charge</h2><p class="text-muted mb-0"><?=htmlspecialchars($m['full_name'])?> · <?=htmlspecialchars($m['anc_number'])?></p></div><a href="view.php?id=<?=$id?>" class="btn btn-light">Back to Record</a></div><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?><div class="card shadow-sm" style="max-width:800px"><div class="card-header bg-white"><strong>Central Invoice Charge</strong></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>"><input type="hidden" name="maternity_id" value="<?=$id?>"><div class="form-group"><label>Maternity Service</label><select name="service_id" class="form-control" required><option value="">Select service...</option><?php $services=$conn->query("SELECT id,service_name,category,department FROM services_master WHERE active=1 AND (category='maternity' OR LOWER(department)='maternity') ORDER BY service_name"); if($services): while($svc=$services->fetch_assoc()): ?><option value="<?=$svc['id']?>"><?=htmlspecialchars($svc['service_name'])?></option><?php endwhile; endif; ?></select><small class="form-text text-muted">The active catalogue price and applicable patient coverage are applied automatically.</small></div><button class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Add to Central Invoice</button></form></div></div></div></div><?php include __DIR__ . '/../includes/footer.php'; ?>