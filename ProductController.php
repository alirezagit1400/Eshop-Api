<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\api\BaseController as BaseController;


use Illuminate\Http\Request;

use App\Group;

use App\Factory;

use App\Area;

use App\Product;
use App\Item;

use App\Rategroup;
use App\Seller;

use Verta;

class ProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id,$area)
    {

  
     $items= Product::find($id)->items()->with(['group'=>function($query) use ($area) {
      //$query->whereIn('area',explode('-',))
        $query->select(['id','title','group_image','updated_at','area','cost','customer'])->byarea($area);
        
       }])->
      //whereIn('group_id',$groups)->
       //orderBy('items.price','desc')->
       get(['id','pack','unit','price','minimum','group_id']);

    


foreach ($items as $key => $value) {

 $rate=Rategroup::rateCompany($value['group']['id']);
 $is=Group::find($value['group']['id'])->isOnline();
  
 $activity=new Verta($value['group']['updated_at']);

 switch ($value['pack']) {
   case 'one':
     $value['pack']='تعداد';
     break;

       case 'box':
     $value['pack']='جعبه';
     break;

       case 'bulk':
     $value['pack']='فله';
     break;
   
   default:
      $value['pack']=null;
     break;
 }

$state = $is ? 'online' : 'off';
 
 array_add($value,'state',$state);  
 array_add($value,'rate',$rate);
array_add($value,'last',$activity->formatDifference());

}
$items= $items->map(function($v,$k){
 return collect($v)->except(['group_id','group.updated_at']);
});
//return $items;
   $items=collect($items)->sortBy('price')->sortByDesc('rate')->values();

       $product=Product::find($id)->get(['id','title','image','factory_id',
        'list_id','sublist_id'])->first();
       //$product=Product::find($id);
    	  return $this->sendResponse(['product'=>$product,'companies'=>$items], 'OK');
    }

 public function view($id,$sub=null) {

  $company=Group::where('id',$id)->first(['id','title','group_image']);
  $rate=$company->rate();
 $company= collect($company)->put('today',$company->tvisits())->put('yesterday',$company->yvisits())->put('rate',$rate);
  $all= Group::where('id',$id)->first()->load(['items'=>function($query) {

   $query->with(['product'=>function($query){

  $query->select('id','title','image','factory_id','list_id','sublist_id');
  $query->with(['category','sublist']);
}])->select(['id','product_id','group_id','price']);
 }]);

//return collect($all->items);
 $products=collect($all->items)->map(function ($v,$k) {

  return collect($v->product)->put('price',$v->price);
  
  //return array_add($v,'gg',44);
  });


if($sub){

$products= $products->where('sublist.id',$sub);
}

 $product=$products->map(function ($item, $key) {

return collect($item)->Only(['id','title','image','price']);

})->values();

if(!isset($sub)) { $product=[];}
$sublists= collect($products)->unique('sublist')->pluck('sublist');
 
 return $this->sendResponse(['company'=>$company,
  'sublist'=>$sublists,'products'=>$product], 'OK');
 }

  }  
