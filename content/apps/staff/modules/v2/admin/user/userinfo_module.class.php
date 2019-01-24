<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 管理员信息
 * @author will
 */
class v2_admin_user_userinfo_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
		$this->authadminSession();
		
        if ($_SESSION['admin_id' ] <= 0 && $_SESSION['staff_id'] <= 0) {
            return new ecjia_error(100, 'Invalid session');
        }
        if ($_SESSION['staff_id']) {
            //商家
            return $this->get_user_info_merchant();
        } else {
            //平台
            return $this->get_user_info_admin();
        }
		
	}

    private function get_user_info_merchant() {
        $result = RC_DB::table('staff_user')->where('user_id', $_SESSION['staff_id'])->first();
        
        $role_name = '';
        if (($result['parent_id'] == 0) && (empty($result['group_id']))) {
            $role_name = '店长';
        } elseif (($result['parent_id'] > 0) && ($result['group_id'] == '-1')) {
            $role_name = '配送员';
        } elseif (($result['parent_id'] > 0) && ($result['group_id'] == '-2')) {
            $role_name = '收银员';
        } else {
            $role_name = RC_DB::table('staff_group')->where('group_id', $result['group_id'])->pluck('group_name');
        }
        
        /*返回connect_user表中open_id和token*/
        $open_id = RC_DB::table('connect_user')->where('connect_code', 'app')->where('user_id', $result['user_id'])->where('user_type', 'merchant')->pluck('open_id');
        $connect_appuser = (new Ecjia\App\Connect\Plugins\EcjiaSyncAppUser($open_id, 'merchant'))->getEcjiaAppUser();
        if ($result) {
            $userinfo = array(
                'id'            => $result['user_id'],
                'username'      => $result['name'],
                'nickname'      => $result['nick_name'],
                'mobile'        => $result['mobile'],
                'email'         => $result['email'],
                'last_login'    => RC_Time::local_date(ecjia::config('time_format'), $result['last_login']),
                'last_ip'       => RC_Ip::area($result['last_ip']),
                'role_name'     => $role_name,
                'avator_img'    => $result['avatar'] ? RC_Upload::upload_url($result['avatar']) : '',
                'action_list'   => $result['action_list'],
                'store_id'      => intval($result['store_id']),
            	'open_id'       => $connect_appuser->open_id ? $connect_appuser->open_id : '',
            	'access_token'  => $connect_appuser->access_token ? $connect_appuser->access_token : '',
            	'refresh_token' => $connect_appuser->refresh_token ? $connect_appuser->refresh_token : '',
            	'user_type'		=> 'merchant'
            );
        } else {
            return new ecjia_error('error', '用户信息不存在');
        }
        
        return $userinfo;
    }

    private function get_user_info_admin() {
        //$db = RC_Model::model('user/admin_user_model');
        $db_role = RC_Loader::load_model('role_model');
        
        //$result = $db->find(array('user_id' => $_SESSION['admin_id']));
        $result = RC_DB::table('admin_user')->where('user_id', $_SESSION['admin_id'])->first();
        
        if (isset($_SESSION['adviser_id']) && !empty($_SESSION['adviser_id'])) {
            $adviser_info = RC_Model::model('achievement/adviser_model')->find(array('id' => $_SESSION['adviser_id']));
            $result['user_name'] = $adviser_info['username'];
            $result['email']     = $adviser_info['email'];
        }
        
        $userinfo = array(
            'id'            => $result['user_id'],
            'username'      => $result['user_name'],
            'email'         => $result['email'],
            'last_login'    => RC_Time::local_date(ecjia::config('time_format'), $result['last_login']),
            'last_ip'       => RC_Ip::area($result['last_ip']),
            'role_name'     => $db_role->where(array('role_id' => $result['role_id']))->get_field('role_name'),
            'avator_img'    => RC_Uri::admin_url('statics/images/admin_avatar.png'),
            'avatar_img'    => RC_Uri::admin_url('statics/images/admin_avatar.png'),
        );
        
        return $userinfo;
    }
}

// end