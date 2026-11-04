<?php
declare(strict_types=1);
require_login();
require_role('manager');
$pdo = db();
$managerId = (int)current_user()['userId'];
$title = 'AI-Assisted Deposit Disputes';
$error = null;

function dispute_owned(PDO $pdo, int $id, int $managerId): ?array {
    $q = $pdo->prepare("SELECT dc.*, tn.tenantId, tn.cautionFee, tn.startDate, tn.endDate, tn.status AS tenancyStatus, t.fullName, t.phone, p.name AS propertyName, u.unitNumber FROM dispute_cases dc JOIN tenancies tn ON tn.tenancyId=dc.tenancyId JOIN tenants t ON t.tenantId=tn.tenantId JOIN units u ON u.unitId=tn.unitId JOIN properties p ON p.propertyId=u.propertyId WHERE dc.disputeId=? AND p.managerId=? LIMIT 1");
    $q->execute([$id, $managerId]); return $q->fetch() ?: null;
}
function dispute_fail(string $message): never { flash('error', $message); redirect(base_url('?page=disputes')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $disputeId = filter_input(INPUT_POST, 'disputeId', FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'create') {
        $tenancyId = filter_input(INPUT_POST, 'tenancyId', FILTER_VALIDATE_INT) ?: 0;
        $check = $pdo->prepare("SELECT tn.tenancyId FROM tenancies tn JOIN units u ON u.unitId=tn.unitId JOIN properties p ON p.propertyId=u.propertyId WHERE tn.tenancyId=? AND p.managerId=?");
        $check->execute([$tenancyId, $managerId]);
        if (!$check->fetchColumn()) dispute_fail('Choose a tenancy belonging to one of your properties.');
        $ins = $pdo->prepare("INSERT INTO dispute_cases (tenancyId,status) VALUES (?, 'pending')");
        $ins->execute([$tenancyId]); $newId=(int)$pdo->lastInsertId();
        audit('create','dispute_cases',(string)$newId,['tenancyId'=>$tenancyId]);
        flash('success','Deposit dispute case created. Upload matching move-in and move-out photos.');
        redirect(base_url('?page=disputes&case='.$newId));
    }

    $case = dispute_owned($pdo, $disputeId, $managerId);
    if (!$case) dispute_fail('The selected dispute was not found under your managed properties.');

    if ($action === 'upload') {
        $phase = (string)($_POST['phase'] ?? '');
        if (!in_array($phase, ['move_in','move_out'], true) || empty($_FILES['photos']['name'][0])) dispute_fail('Choose the evidence stage and at least one image.');
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $dir = dirname(__DIR__,2).'/storage/uploads/disputes/'.$disputeId;
        if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) dispute_fail('Could not create the private evidence folder.');
        $finfo = new finfo(FILEINFO_MIME_TYPE); $saved=0;
        foreach ($_FILES['photos']['tmp_name'] as $i=>$tmp) {
            if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (!is_uploaded_file($tmp) || (int)$_FILES['photos']['size'][$i] > 12*1024*1024) continue;
            $mime=$finfo->file($tmp); if (!isset($allowed[$mime])) continue;
            $stored=bin2hex(random_bytes(20)).'.'.$allowed[$mime];
            if (!move_uploaded_file($tmp,$dir.'/'.$stored)) continue;
            $caption=trim((string)($_POST['caption'] ?? ''));
            $q=$pdo->prepare('INSERT INTO dispute_evidence (disputeId,phase,fileName,storedName,mimeType,fileSize,caption,uploadedBy) VALUES (?,?,?,?,?,?,?,?)');
            $q->execute([$disputeId,$phase,basename((string)$_FILES['photos']['name'][$i]),$stored,$mime,(int)filesize($dir.'/'.$stored),$caption!==''?substr($caption,0,255):null,$managerId]);
            $saved++;
        }
        if (!$saved) dispute_fail('No images were saved. Use JPG, PNG or WebP files no larger than 12 MB each.');
        audit('upload_evidence','dispute_cases',(string)$disputeId,['phase'=>$phase,'count'=>$saved]);
        flash('success',$saved.' evidence image(s) uploaded successfully.');
        redirect(base_url('?page=disputes&case='.$disputeId));
    }

    if ($action === 'analyze') {
        if (!function_exists('curl_init')) dispute_fail('PHP cURL is not enabled in XAMPP. Enable extension=curl in php.ini and restart Apache.');
        $q=$pdo->prepare("SELECT evidenceId,phase,storedName,mimeType FROM dispute_evidence WHERE disputeId=? ORDER BY evidenceId");
        $q->execute([$disputeId]); $all=$q->fetchAll();
        $ins=array_values(array_filter($all,fn($r)=>$r['phase']==='move_in'));
        $outs=array_values(array_filter($all,fn($r)=>$r['phase']==='move_out'));
        if (!$ins || !$outs) dispute_fail('Upload at least one move-in image and one move-out image before analysis.');
        $dir=dirname(__DIR__,2).'/storage/uploads/disputes/'.$disputeId;
        $base=rtrim(getenv('AI_SERVICE_URL')?:'http://127.0.0.1:8001','/');
$apiKey = getenv('AI_API_KEY') ?: '';
if ($apiKey === '') {
    dispute_fail('AI service API key is not configured.');
}
        $reports=[];
        foreach ($ins as $i=>$in) {
            $out=$outs[$i] ?? $outs[0];
            $inPath=$dir.'/'.$in['storedName']; $outPath=$dir.'/'.$out['storedName'];
            if (!is_file($inPath)||!is_file($outPath)) continue;
            $ch=curl_init($base.'/compare');
            $post=['move_in'=>new CURLFile($inPath,$in['mimeType'],'move-in-image'),'move_out'=>new CURLFile($outPath,$out['mimeType'],'move-out-image')];
           curl_setopt_array($ch,[
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => ['X-API-Key: '.$apiKey]
]);
            $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlError=curl_error($ch); curl_close($ch);
            if ($body===false || $status!==200) dispute_fail('Local vision service could not analyse the images. Start the Python service and try again. '.($curlError?:'HTTP '.$status));
            $decoded=json_decode((string)$body,true); if (!is_array($decoded)) dispute_fail('The local vision service returned an unreadable report.');
            $reports[]=['move_in_evidence_id'=>(int)$in['evidenceId'],'move_out_evidence_id'=>(int)$out['evidenceId'],'result'=>$decoded];
        }
        if (!$reports) dispute_fail('No valid image pairs could be analysed.');
        $summary=['pairs_analysed'=>count($reports),'reports'=>$reports,'notice'=>'Visual changes are not proof of damage or tenant responsibility. Human inspection and manager review are required.'];
        $json=json_encode($summary,JSON_THROW_ON_ERROR);
        $pdo->beginTransaction();
        try {
            $a=$pdo->prepare('INSERT INTO dispute_assessments (disputeId,engine,resultJson,createdBy) VALUES (?,?,?,?)');
            $a->execute([$disputeId,'OpenCV local visual-difference analysis',$json,$managerId]);
            $classification='Visual comparison completed';
            $j=$pdo->prepare("UPDATE dispute_cases SET aiClassification=?, aiJustification=?, status='reviewed' WHERE disputeId=?");
            $j->execute([$classification,'Local image comparison completed for '.$summary['pairs_analysed'].' image pair(s). This identifies pixel-level visual changes only; it does not classify damage, assign responsibility, or calculate repair cost.',$disputeId]);
            $pdo->commit();
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); error_log($e->getMessage()); dispute_fail('Could not save the assessment report.'); }
        audit('local_visual_analysis','dispute_cases',(string)$disputeId,['pairs'=>count($reports)]);
        flash('success','Local image comparison completed. Review the report and original evidence before deciding.');
        redirect(base_url('?page=disputes&case='.$disputeId));
    }

    if ($action === 'settle') {
        $deduction=trim((string)($_POST['finalDeduction']??'')); $decision=trim((string)($_POST['managerNotes']??''));
        if (!is_numeric($deduction)||(float)$deduction<0||(float)$deduction>(float)$case['cautionFee']) dispute_fail('Final deduction must be between ₦0 and the caution fee (₦'.number_format((float)$case['cautionFee'],2).').');
        if ($decision==='') dispute_fail('Enter the manager’s reason for the decision.');
        $ded=(float)$deduction; $refund=max(0,(float)$case['cautionFee']-$ded);
        $q=$pdo->prepare("UPDATE dispute_cases SET finalDeduction=?,refundAmount=?,aiJustification=CONCAT(COALESCE(aiJustification,''),CHAR(10),CHAR(10),'Manager decision: ',?),status='resolved',resolvedDate=CURDATE() WHERE disputeId=?");
        $q->execute([number_format($ded,2,'.',''),number_format($refund,2,'.',''),$decision,$disputeId]);
        audit('settle','dispute_cases',(string)$disputeId,['finalDeduction'=>$ded,'refundAmount'=>$refund,'managerNotes'=>$decision]);
        flash('success','Dispute settled. Final deduction: ₦'.number_format($ded,2).'; refund: ₦'.number_format($refund,2).'.');
        redirect(base_url('?page=disputes&case='.$disputeId));
    }
    if ($action === 'reopen') {
        $q=$pdo->prepare("UPDATE dispute_cases SET status='reviewed',resolvedDate=NULL WHERE disputeId=?"); $q->execute([$disputeId]);
        audit('reopen','dispute_cases',(string)$disputeId); flash('success','Dispute reopened for manager review.'); redirect(base_url('?page=disputes&case='.$disputeId));
    }
}

$tenanciesQ=$pdo->prepare("SELECT tn.tenancyId,t.fullName,p.name AS propertyName,u.unitNumber,tn.cautionFee,tn.status FROM tenancies tn JOIN tenants t ON t.tenantId=tn.tenantId JOIN units u ON u.unitId=tn.unitId JOIN properties p ON p.propertyId=u.propertyId WHERE p.managerId=? ORDER BY tn.startDate DESC");
$tenanciesQ->execute([$managerId]); $tenancies=$tenanciesQ->fetchAll();
$listQ=$pdo->prepare("SELECT dc.disputeId,dc.status,dc.createdAt,dc.finalDeduction,dc.refundAmount,t.fullName,p.name AS propertyName,u.unitNumber,tn.cautionFee FROM dispute_cases dc JOIN tenancies tn ON tn.tenancyId=dc.tenancyId JOIN tenants t ON t.tenantId=tn.tenantId JOIN units u ON u.unitId=tn.unitId JOIN properties p ON p.propertyId=u.propertyId WHERE p.managerId=? ORDER BY dc.createdAt DESC");
$listQ->execute([$managerId]); $cases=$listQ->fetchAll();
$selectedId=filter_input(INPUT_GET,'case',FILTER_VALIDATE_INT)?:0; $selected=$selectedId?dispute_owned($pdo,$selectedId,$managerId):null;
$evidence=[]; $assessments=[];
if($selected){$q=$pdo->prepare('SELECT * FROM dispute_evidence WHERE disputeId=? ORDER BY phase,evidenceId');$q->execute([$selectedId]);$evidence=$q->fetchAll();$q=$pdo->prepare('SELECT * FROM dispute_assessments WHERE disputeId=? ORDER BY createdAt DESC,assessmentId DESC');$q->execute([$selectedId]);$assessments=$q->fetchAll();}
require dirname(__DIR__).'/views/layouts/header.php';
require dirname(__DIR__).'/views/disputes/index.php';
require dirname(__DIR__).'/views/layouts/footer.php';
