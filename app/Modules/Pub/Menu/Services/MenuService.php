<?php


namespace App\Modules\Pub\Menu\Services;


use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MenuService
{
    private $menu;
    protected $menuitems;

    public function __construct($uid = null)
    {
        $this->uid = $uid;
        $this->user = !empty($this->uid) ? User::find($this->uid) : null;
        $this->menu_tree = cache()->get('menu_tree_' . $this->uid);
    }

    public function build()
    {
        $menuitems = Menu::isLive()
        ->ofSort(['parent_id' => 'asc', 'sort' => 'asc'])
        ->with('accesses')
        ->with('children')
        ->get();

        $menuitems = $menuitems->filter(function($item) {
            if(Gate::any($item->accesses->pluck('code')->toArray())) return $item;
        });

        return $menuitems;
    }

    public function getMenuTree()
    {
        $ret = $this->buildTree();
        return $ret;
    }

    public function buildTree($items = [])
    {

        if(empty($items)) $items = $this->build();
        $grouped = $items->groupBy('parent_id');

        foreach ($items as $item) {
            if ($grouped->has($item->id)) {
                /** @var TYPE_NAME $item */
                $item->children = $grouped[$item->id];
            }
        }

        return $items->where('parent_id', null);
    }


    public function jsTree() {
            $menuitems = Menu::ofSort(['parent_id' => 'asc', 'sort' => 'asc'])
                ->get();
            $menuitems->map(function($item) {
               $item->text = $item->name;
               $item->icon = "";
               $item->state = ['opened' => 1];
            });


            return $this->buildTree($menuitems);
    }

    public function updateFromArray($data)
    {
        function get_child($ar, $parent_id, $arReturn) {
            foreach($ar as $row) {
                if(strpos($row['id'], "j") !== false)
                {
                    $item = Menu::where(['parent_id' => $parent_id, 'name' => $row['name']])->get();

                    # проверим ID
                    if(!empty($item->id))
                    {
                        $row['id'] = $item->id;
                    } else {

                        $menu = new Menu();
                        $menu->parent_id = $parent_id;
                        $menu->name = $row['name'];
                        $menu->active = 1;
                        $menu->save();
                        $row['id'] = $menu->id;
                    }
                }

                $arReturn[$parent_id][] = [
                    "id" => $row['id'],
                    "name" => $row['name']
                ];
                if(!empty($row['children'])) $arReturn = get_child($row['children'], $row['id'], $arReturn);
            }
            return $arReturn;
        }

        $arFlat = get_child($data, 0, []);
        foreach($arFlat as $k => $ar)
            foreach($ar as $cat)
                $arIDHave[] = $cat['id'];

        // удаление
        Menu::whereNotIn('id', $arIDHave)->delete();


        foreach($arFlat as $parent_id => $ar)
        {
            $sort = 0;
            foreach($ar as $row)
            {
                $menu = Menu::find($row['id']);
                $menu->parent_id = $parent_id;
                $menu->name = $row['name'];
                $menu->sort = $sort;
                $menu->save();
                $sort += 100;
            }
        }

        return true;
    }
}
