<?php
declare(strict_types=1);
require_login(); require_role('manager');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$q=db()->prepare("SELECT de.storedName,de.mimeType,dc.disputeId FROM dispute_evidence de JOIN dispute_cases dc ON dc.disputeId=de.disputeId JOIN tenancies tn ON tn.tenancyId=dc.tenancyId JOIN units u ON u.unitId=tn.unitId JOIN properties p ON p.propertyId=u.propertyId WHERE de.evidenceId=? AND p.managerId=?");
$q->execute([$id,(int)current_user()['userId']]); $row=$q->fetch();
if(!$row){http_response_code(404);exit('Evidence not found.');}
$path=dirname(__DIR__,2).'/storage/uploads/disputes/'.$row['disputeId'].'/'.$row['storedName'];
if(!is_file($path)){http_response_code(404);exit('Evidence file is missing.');}
header('Content-Type: '.$row['mimeType']); header('Content-Length: '.filesize($path)); header('X-Content-Type-Options: nosniff'); header('Content-Disposition: inline; filename="evidence-'.$id.'"'); readfile($path); exit;
