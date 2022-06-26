<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\api\BaseController as BaseController;

use Illuminate\Http\Request;
use App\Group;
use App\Rategroup;
use App\Seller;
use App\Factory;

use App\City;
use App\Area;

class HomeController extends BaseController
{


  public function index($id){



$brands=Factory::with('part')->with('rates')->latest()->get(['id','title','image','part_id','updated_at'])->groupBy('part_id');
 $brands=$brands->map(function ($v,$k){

return collect($v->first())->only('part')->put('brands',$v->map(function ($v,$k){
  
    $rate= ($v['rates']->count()>0)?$v['rates']->avg('rate'):2.5;
    
    return collect($v)->put('rate',$rate)->only('id','title','image','rate','updated_at');
})->sortByDesc(function($v,$k){

 $updated=strtotime($v['updated_at']);
 
 if(str_contains($v['title'],'به زودی')){

 return  ($v['rate']/5)+($updated/time())-2;
 }
 
  return ($v['rate']/5)+($updated/time());
})->values()->map(function($v,$k){

   $state = ($k<2) ? 'tab':'free'; 

   return $v->put('state',$state);

}));

})->values();


 $city=Area::find($id);
 $companies=Group::where('city_id',$city->city_id)->byarea($id)->has('items.product.factory.part')->get(['id','area','title','group_image','activity']);
 
   $parts=$companies->map(function ($i,$k)  {
	  
if(collect($i->items)->isNotEmpty()) {
  $rate=Rategroup::rateCompany($i['id']);

 $is=Group::find($i['id'])->isOnline();
   

$state = $is ? 'online' : 'off';


  // $i=collect($i)->put('rate',$rate)->put('state',$state);
	return ['group'=>collect($i)->put('rate',$rate)->put('state',$state)->except('items'),'parts'=>collect($i->items)->unique('product.factory.part')->
  pluck('product.factory.part')];
}

	});



  $groups=$parts->map(function ($i,$k)  {
$id=$i['group'];
   $n=collect($i['parts'])->map(function($v,$k) use ($id) {
    return ['part'=>$v,'group'=>$id];
   });

 return $n;

})->collapse()->sortBy('part.id')->groupBy('part.id');

$groups= $groups->map(function ($v,$k){
 return collect($v->first())->only(['part'])->put('companies',collect($v->pluck('group'))->map(function($v,$k){
  $type = ($k<2) ? 'tab':'free'; 

  return $v->put('type',$type);

 })->sortByDesc(function($v,$k){

 $activity=$v['activity']/time();

   if($v['type']=='tab'){
      
        return ($v['rate']/15)+(($activity*2)/3)+1;
    }
 
  return ($v['rate']/15)+(($activity*2)/3);
})->values()->map(function($v,$k){
 return $v->except('activity');
}));
//return $v->first()->only(['part']);
})->values();

 //$companies=collect($companies)->sortByDesc('rate')->values();

    return $this->sendResponse(['brand'=>$brands,'company'=>$groups], 'OK');
  }


}