<?php
include_once "../tool/projeqtor.php";

$class=null;
if (isset($_REQUEST['class'])) {
  $class=$_REQUEST['class'];
}
Security::checkValidClass($class);
if ($class=='Replan' or $class=='Construction' or $class=='Fixed') {
  $class='Project';
}
$id=null;
if (isset($_REQUEST['id'])) {
  $id=$_REQUEST['id'];
}
Security::checkValidId($id);
$scale='day';
if (isset($_REQUEST['scale'])) {
  $scale=$_REQUEST['scale'];
}
if ($scale!='day' and $scale!='week') {
  echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('ganttDetailScaleError')."</div>";
  return;
}

$objectClassManual = RequestHandler::getValue('objectClassManual');
if($objectClassManual == 'ResourcePlanning' ){
  $idAssignment = RequestHandler::getId('idAssignment');
}

$dates=array();
$work=array();
$maxCapacity=array();
$minCapacity=array();
$ressAll=array();
$start=null;
$end=null;

if ($class=='Resource' or $class=='ResourceTeam') {
  echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay')."</div>";
  return;
}
$crit=array('refType'=>$class,'refId'=>$id);

$pe=SqlElement::getSingleSqlElementFromCriteria($class.'PlanningElement', $crit);
if ($pe->assignedWork==0 and $pe->leftWork==0 and $pe->realWork==0) {
  echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay')."</div>";
  return;
}

if($objectClassManual == 'ResourcePlanning' ){
  $crit=array('refType'=>$class,'refId'=>$id,'idAssignment'=>$idAssignment);
}

$wk=new Work();
$wkLst=$wk->getSqlElementsFromCriteria($crit);
foreach($wkLst as $wk) {
  $dates[$wk->workDate]=$wk->workDate;
  if (!$start or $start>$wk->workDate) $start=$wk->workDate;
  if (!$end or $end<$wk->workDate) $end=$wk->workDate;
  if (! isset($work[$wk->idAssignment])) $work[$wk->idAssignment]=array();
  if (! isset($work[$wk->idAssignment]['resource'])) {
    $ress=new ResourceAll($wk->idResource);
    $ressAll[$wk->idResource]=$ress;
    $work[$wk->idAssignment]['capacity']=($ress->capacity>1)?$ress->capacity:'1';
    $work[$wk->idAssignment]['resource']=$ress->name;
    $work[$wk->idAssignment]['idResource']=$ress->id;
    if ($ress->isResourceTeam) {
      $ass=new Assignment($wk->idAssignment);
      $work[$wk->idAssignment]['capacity']=($ass->capacity>1)?$ass->capacity:'1';
    }
    if ($work[$wk->idAssignment]['capacity']>1) {
      $work[$wk->idAssignment]['resource'].=' ('.i18n('max').' = '.htmlDisplayNumericWithoutTrailingZeros($work[$wk->idAssignment]['capacity']).' '.i18n('days').')';
    }
  }
  $work[$wk->idAssignment][$wk->workDate]=array('work'=>$wk->work,'type'=>'real');
  $maxCapacity[$wk->idResource]=$work[$wk->idAssignment]['capacity'];
  $minCapacity[$wk->idResource]=$work[$wk->idAssignment]['capacity'];
}
$wk=new PlannedWork();
$wkLst=$wk->getSqlElementsFromCriteria($crit);
foreach($wkLst as $wk) {
  $dates[$wk->workDate]=$wk->workDate;
  if (!$start or $start>$wk->workDate) $start=$wk->workDate;
  if (!$end or $end<$wk->workDate) $end=$wk->workDate;
  if (! isset($work[$wk->idAssignment])) $work[$wk->idAssignment]=array();
  if (! isset($work[$wk->idAssignment]['resource'])) {
    $ress=new ResourceAll($wk->idResource);
    $ressAll[$wk->idResource]=$ress;
    $work[$wk->idAssignment]['capacity']=($ress->capacity>1)?$ress->capacity:'1';
    $work[$wk->idAssignment]['resource']=$ress->name;
    $work[$wk->idAssignment]['idResource']=$ress->id;
    if ($ress->isResourceTeam) {
      $ass=new Assignment($wk->idAssignment);
      $work[$wk->idAssignment]['capacity']=($ass->capacity>1)?$ass->capacity:'1';
    }
    if ($work[$wk->idAssignment]['capacity']>1) {
      $work[$wk->idAssignment]['resource'].=' ('.i18n('max').' = '.htmlDisplayNumericWithoutTrailingZeros($work[$wk->idAssignment]['capacity']).' '.i18n('days').')';
    }
  }
  if (! isset($work[$wk->idAssignment][$wk->workDate]) ) {
    $work[$wk->idAssignment][$wk->workDate]=array('work'=>$wk->work,'type'=>'planned');
  }
  $maxCapacity[$wk->idResource]=$work[$wk->idAssignment]['capacity'];
  $minCapacity[$wk->idResource]=$work[$wk->idAssignment]['capacity'];
}
if ($pe->idPlanningMode=='22') { // RECW
  $start=$pe->plannedStartDate;
  $end=$pe->plannedEndDate;
}
if (!$start or !$end) {
  if ($pe->elementary) echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay').'<br/>'.i18n('planningCalculationRequired')."</div>";
  else echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay')."</div>";
	return;
}

if($objectClassManual != 'ResourcePlanning' ){
  if ($pe->plannedStartDate && $pe->plannedStartDate<$start){
    $start=$pe->plannedStartDate;
  }
}

$variableCapacity=array();
$dt=$start;
while ($dt<=$end) {
  if (!isset($dates[$dt])) {
    $dates[$dt]=$dt;
  }
  foreach ($ressAll as $ress) {
    if (!isset($variableCapacity[$ress->id])) $variableCapacity[$ress->id]=array();
    $capa=$ress->getSurbookingCapacity($dt);
    if (! $ress->isResourceTeam) {
      if ($capa!=$ress->capacity) {
        $variableCapacity[$ress->id][$dt]=$capa;
      }
      if ($capa>$maxCapacity[$ress->id]) $maxCapacity[$ress->id]=$capa;
      if ($capa<$minCapacity[$ress->id]) $minCapacity[$ress->id]=$capa;
    }
  }
  $dt=addDaysToDate($dt, 1);
}
ksort($dates);

$width=20;
echo '<table id="planningBarDetailTable" style="height:'.(count($work)*22).'px;background-color:#FFFFFF;border-collapse: collapse;marin:0;padding:0">';
$heightNormal=20;
$heightCapacity=20;
foreach ($work as $resWork) {
  $resObj=$ressAll[$resWork['idResource']];
  echo '<tr style="height:20px;border:1px solid #505050;">';
  $overCapa=null;
  $underCapa=null;
  foreach ($dates as $dt) {
    $color="#ffffff";
    $height=20; $w=0;    
    $capacityTop=$maxCapacity[$resWork['idResource']]; //$resWork['capacity'];
    if (!isset($variableCapacity[$resWork['idResource']][$dt])) {
      $heightNormal=20;
      $heightCapacity=20;
    } else {
      $tmp=$ressAll[$resWork['idResource']];
      if ($variableCapacity[$resWork['idResource']][$dt]>$tmp->capacity) {
        if (!$overCapa or $variableCapacity[$resWork['idResource']][$dt]>$overCapa) {
          $overCapa=$variableCapacity[$resWork['idResource']][$dt];
        }
      } else {
        if (!$underCapa or $variableCapacity[$resWork['idResource']][$dt]<$underCapa) {
          $underCapa=$variableCapacity[$resWork['idResource']][$dt];
        }
      }
      $heightNormal=round(20*$resWork['capacity']/$capacityTop,0);
      $heightCapacity=round(20*$variableCapacity[$resWork['idResource']][$dt]/$capacityTop,0);
    }
    if ($capacityTop==0) $capacityTop=1;
    if (isset($resWork[$dt])) {
      $w=$resWork[$dt]['work'];       
      if (!$pe->validatedEndDate or $dt<=$pe->validatedEndDate) {
        $color=($resWork[$dt]['type']=='real')?"#507050":"#50BB50";  
      } else {
        $color=($resWork[$dt]['type']=='real')?"#705050":"#BB5050";
      }
      $height=round($w*20/$capacityTop,0);
    }
    echo '<td style="padding:0;width:'.$width.'px;border-right:1px solid #eeeeee;position:relative;">';
    echo '<div style="display:block;background-color:'.$color.';position:absolute;bottom:0px;left:0px;width:100%;height:'.$height.'px;"></div>';
    if ($maxCapacity[$resWork['idResource']]!=$resWork['capacity'] or $minCapacity[$resWork['idResource']]!=$resWork['capacity']) {
      echo '<div style="display:block;background-color:transparent;position:absolute;bottom:0px;left:0px;width:100%;border-top:1px solid grey;height:'.$heightNormal.'px;"></div>';
    }
    if ($heightNormal!=$heightCapacity and isset($variableCapacity[$resWork['idResource']][$dt])) {
      echo '<div style="display:block;background-color:transparent;position:absolute;bottom:0px;left:0px;width:100%;border-top:1px solid red;height:'.$heightCapacity.'px;"></div>';
    }
    echo '</td>';
  }
  echo '<td style="border-left:1px solid #505050;"><div style="width:200px; max-width:200px;overflow:hidden; text-align:left">&nbsp;'.$resWork['resource'];
  if ($overCapa) echo '&nbsp;<img style="width:10px" src="../view/img/arrowUp.png" />&nbsp;'.htmlDisplayNumericWithoutTrailingZeros($overCapa);
  if ($underCapa) echo '&nbsp;<img style="width:10px" src="../view/img/arrowDown.png" />&nbsp;'.htmlDisplayNumericWithoutTrailingZeros($underCapa);
  echo '&nbsp;</div></td>';
  echo '</tr>';
}
echo '</table>';