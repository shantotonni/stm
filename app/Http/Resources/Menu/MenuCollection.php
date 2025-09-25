<?php

namespace App\Http\Resources\Menu;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MenuCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($menu){
                return [
                    'Id'=>$menu->Id,
                    'MenuID'=>$menu->MenuID,
                    'Name'=>$menu->Name,
                    'Icon'=>$menu->Icon,
                    'Status'=>$menu->Status,
                    'Link'=>$menu->Link,
                    'Ordering'=>$menu->Ordering,
                    'MenuName'=>isset($menu->menu) ? $menu->menu->Name : '',
                ];
            })
        ];
    }
}
