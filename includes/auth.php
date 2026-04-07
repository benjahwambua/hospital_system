<?php
// includes/auth.php
function current_user_id(){ return $_SESSION['user_id'] ?? null; }
function current_user_role(){ return $_SESSION['role'] ?? null; }
function current_user_is_super(){ return !empty($_SESSION['is_super']); }

function require_role($roles=[]){
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array(current_user_role(), $roles) && !current_user_is_super()){
        http_response_code(403); echo "Forbidden"; exit;
    }
}
function require_super(){ if (!current_user_is_super()){ http_response_code(403); echo "Forbidden (super only)"; exit; } }
