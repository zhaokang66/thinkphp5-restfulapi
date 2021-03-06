<?php
/**
 * @author: Axios
 *
 * @email: axioscros@aliyun.com
 * @blog:  http://hanxv.cn
 * @datetime: 2017/4/25 15:44
 */
namespace app\common\controller;

use think\Controller;
use think\Session;
use think\Request;
use think\Config;
use think\Cache;
use think\Env;
use think\Db;

class HomeBase extends Controller{
    protected $param;

    protected $user;

    protected $themes;

    protected $template_dir;

    protected $method;

    protected $menu;

    protected $menu_path;

    protected $debug;

    protected $config;

    function __construct(Request $request = null)
    {
        parent::__construct($request);
        Config::load(APP_PATH."/admin/config.php");
        $this->config = Config::get('setting.admin');
        $this->param = $request->param();
        $this->method = $request->method();
        $this->user = user_info();

        if(Env::get("debug.status")){
            $this->makeMenuTree(0,$this->menu);
            Cache::set('menu',$this->menu,3600);
            $this->makePathMenu();
            Cache::set('path_menu',$this->menu_path,3600);
        }else{
            $this->menu = Cache::get("menu");
            if(empty($this->menu)){
                $this->makeMenuTree(0,$this->menu);
                Cache::set('menu',$this->menu,3600);
            }

            $this->menu_path = Cache::get("path_menu");
            if(empty($this->menu_path)){
                $this->makePathMenu();
                Cache::set('path_menu',$this->menu_path,3600);
            }
        }

        $this->assign('title',isset($this->menu_path[$this->request->path()]['title'])?$this->menu_path[$this->request->path()]['title']:"");
        $this->assign('menu',$this->menu);
    }

    protected function makeMenuTree($parent_id=0,&$parent_menu=[],$all=false){
        $Menu = Db::name("menu")->alias('menu')->where('menu.parent_id',$parent_id);
        if(!$all){
            $Menu->where('show',0);
        }
        $Menu->join("__ROLE_NODE__ rn",'rn.menu_id=menu.id','left')
            ->field('menu.* ');
        if($this->user['username']!="admin"){
            $Menu->where('rn.role_id',$this->user['role_id']);
        }

        $menu = $Menu->order('sort asc')->select();
        foreach ($menu as $m){
            $parent_menu[$m['id']] = $m;
            $this->makeMenuTree($m['id'],$parent_menu[$m['id']]['sub'],$all);
        }
        return $menu;
    }

    private function makePathMenu(){
        $menu = Db::name("menu")->where('parent_id','neq',0)->order('sort asc')->select();
        foreach ($menu as $m){
            $key = strval($m['controller']."/".$m['func']);
            $this->menu_path[$key] = $m;
        }
        return $menu;
    }

    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $this->themes = $this->config['themes'];
        $this->template_dir = $this->themes.":".strtolower($this->request->module()).":".strtolower($this->request->controller()).":".$template;
        $config['view_path'] = Config::get("template.view_path").$this->themes."/";
        return parent::fetch($this->template_dir, $vars, $replace, $config); // TODO: Change the autogenerated stub
    }

    public function _empty(){
        echo __FUNCTION__;
        return "the function not exits";
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        Session::set('last_url',$this->request->url());
    }
}