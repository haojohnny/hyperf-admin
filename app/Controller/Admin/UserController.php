<?php


namespace App\Controller\Admin;

use App\Exception\InvalidAccessException;
use App\Exception\InvalidArgumentsException;
use App\Exception\InvalidRequestMethodException;
use App\Exception\ResultException;
use App\Exception\UserNotFoundException;
use App\Logic\Admin\UserLogic;
use App\Service\AuthService;
use App\Service\UserService;
use App\Validate\UserValidate;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use App\Controller\Controller;
use App\Util\Token;

/**
 * @menu 用户管理
 * @AutoController()
 * Class UserController
 * @package App\Controller\Admin
 */
class UserController extends Controller
{
    /**
     * @Inject()
     * @var UserLogic
     */
    private $logic;

    /**
     * @auth 列表
     * @return mixed
     */
    public function list()
    {
        if (!$this->isGet()) {
            throw new InvalidRequestMethodException();
        }
        $query = $this->request->all();

        $users = $this->logic->getList($query);

        return $this->response->success($users['list'], $users['count']);
    }

    /**
     * @ignore 个人查看【基本资料】
     * @return mixed
     */
    public function get()
    {
        $user_id = Token::instance()->getUserId();

        $user = $this->logic->getUser($user_id);

        if (empty($user)) {
            throw new UserNotFoundException();
        }

        $authService = di(AuthService::class);

        $roles = $authService->select(['status' => 1], ['id', 'title'], 1, 999)->toArray();
        $user['roles'] = $roles;

        //TODO 去掉password
        return $this->response->success($user);
    }

    /**
     * @auth 查看
     * @return mixed
     */
    public function info()
    {
//        $user_id = Token::instance()->getUserId();

        $user_id = $this->request->query('id', '');

        $info = [];
        if ($user_id) {
            $info = $this->logic->getUser($user_id);

            unset($info['password']);
        }
        $authService = di(AuthService::class);

        $roles = $authService->select(['status' => 1], ['id', 'title'], 1, 999)->toArray();
        $info['roles'] = $roles;
        //TODO 去掉password
        return $this->response->success($info);
    }

    /**
     * @ignore 保存【个人保存资料】
     */
    public function save()
    {

        $user_id = Token::instance()->getUserId();

        $role_id = $this->request->post('role_id', '');
        $nickname = $this->request->post('nickname', '');
        $gender = $this->request->post('gender', '');
        $avatar = $this->request->post('avatar', '');
        $mobile = $this->request->post('mobile', '');
        $email = $this->request->post('email', '');
        $remark = $this->request->post('remark', '');

        $data = [
            'id' => $user_id,
            'role_id' => $role_id,
            'nickname' => $nickname,
            'gender' => $gender,
            'avatar' => $avatar,
            'mobile' => $mobile,
            'email' => $email,
            'remark' => $remark,
        ];
        $validate = di(UserValidate::class);

        if (!$validate->scene('edit')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $this->logic->save($user_id, $role_id, $nickname, $gender, $avatar, $mobile, $email, $remark);

        return $this->response->success([], 0, '保存成功！');

    }

    /**
     * @auth 编辑
     */
    public function edit()
    {

        $user_id = $this->request->post('id', '');

        $role_id = $this->request->post('role_id', '');
        $nickname = $this->request->post('nickname', '');
        $gender = $this->request->post('gender', '');
        $avatar = $this->request->post('avatar', '');
        $mobile = $this->request->post('mobile', '');
        $email = $this->request->post('email', '');
        $remark = $this->request->post('remark', '');

        $data = [
            'id' => $user_id,
            'role_id' => $role_id,
            'nickname' => $nickname,
            'gender' => $gender,
            'avatar' => $avatar,
            'mobile' => $mobile,
            'email' => $email,
            'remark' => $remark,
        ];
        $validate = di(UserValidate::class);

        if (!$validate->scene('edit')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $this->logic->save($user_id, $role_id, $nickname, $gender, $avatar, $mobile, $email, $remark);

        return $this->response->success([], 0, '保存成功！');

    }

    /**
     * @auth 添加
     */
    public function add()
    {

        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');
        $role_id = $this->request->post('role_id', '');
        $nickname = $this->request->post('nickname', '');
        $gender = $this->request->post('gender', '');
        $avatar = $this->request->post('avatar', '');
        $mobile = $this->request->post('mobile', '');
        $email = $this->request->post('email', '');
        $status = 1;//todo status
        $remark = $this->request->post('remark', '');

        $data = [
            'username' => $username,
            'password' => $password,
            'role_id' => $role_id,
            'nickname' => $nickname,
            'gender' => $gender,
            'avatar' => $avatar,
            'mobile' => $mobile,
            'email' => $email,
            'status' => $status,
            'remark' => $remark,
        ];

        $validate = di(UserValidate::class);

        if (!$validate->scene('add')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $add = $this->logic->add($username, $password, $role_id, $nickname, $gender, $avatar, $mobile, $email, $status, $remark);
        if (!$add) {
            throw new ResultException('添加失败！');
        }
        return $this->response->success([], 0, '添加成功！');

    }

    /**
     * @auth 禁用
     */
    public function forbid()
    {
        if (!$this->isPost()) {
            throw new InvalidAccessException();
        }

        $id = $this->request->post('id', '');

        $data = [
            'id' => $id,
        ];
        $method = __FUNCTION__;
        $validate = di(UserValidate::class);
        if (!$validate->scene('base')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        //TODO 该角色下是否存在用户

//        $logic = new AuthLogic();

        $res = $this->logic->$method((int)$id);

        if (false === $res) {
            throw new ResultException('禁用失败！');
        }

        return $this->response->success([], 0, '禁用成功！');

    }

    /**
     * @auth 启用
     */
    public function resume()
    {
        if (!$this->isPost()) {
            throw new InvalidAccessException();
        }

        $id = $this->request->post('id', '');

        $data = [
            'id' => $id,
        ];

        $method = __FUNCTION__;
        $validate = di(UserValidate::class);
        if (!$validate->scene('base')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $res = $this->logic->$method((int)$id);

        if (false === $res) {
            throw new ResultException('启用失败！');
        }

        return $this->response->success([], 0, '启用成功！');

    }

    /**
     * @ignore 修改密码
     */
    public function password()
    {
        //个人修改密码

        if (!$this->isPost()) {
            throw new InvalidAccessException();
        }

        $user_id = Token::instance()->getUserId();

        $oldPassword = $this->request->post('oldPassword', '');
        $password = $this->request->post('password', '');
        $rePassword = $this->request->post('rePassword', '');

        $data = [
            'oldPassword' => $oldPassword,
            'password' => $password,
            'rePassword' => $rePassword,
        ];

        $validate = di(UserValidate::class);

        if (!$validate->scene('password')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $res = $this->logic->password($user_id, $oldPassword, $password);

        if (false === $res) {
            throw new ResultException('修改失败！');
        }

        return $this->response->success([], 0, '修改成功！');

    }

    /**
     * @auth 修改密码
     */
    public function setPassword()
    {
        //管理员修改其他人密码
        if (!$this->isPost()) {
            throw new InvalidAccessException();
        }

        $user_id = $this->request->post('user_id', '');
        $password = $this->request->post('password', '');

        $data = [
            'user_id' => $user_id,
            'password' => $password,
        ];

        $validate = di(UserValidate::class);

        if (!$validate->scene('setPassword')->check($data)) {
            throw new InvalidArgumentsException($validate->getError());
        }

        $res = $this->logic->setPassword($user_id, $password);

        if (false === $res) {
            throw new ResultException('修改失败！');
        }

        return $this->response->success([], 0, '修改成功！');
    }

}