<?php
namespace app\index\validate;
use think\Validate;
/**
 * 应用场景：关于验证类
 * @since   2018/06/21 创建
 * @author  itarvin itarvin@163.com
 */
class userValidate extends Validate
{

    /**
     * 应用场景：规则
     */
    protected $rule = [
        'email'     =>  'require|email',
        'username'  =>  'require',
        'password'  =>  'require|min:5|max:50|confirm',
        'passwords'  =>  'require|min:5|max:50',
    ];

    /**
     * 应用场景：提示
     */
    protected $message  =   [
        'username.require' => '必须填写用户名',
        'email.require'    => '必须填写邮箱地址',
        'email.email'      => '邮箱地址错误',
        'password.min'     => '账户密码最少5个字符',
		'password.max'     => '账户密码最大50个字符',
        'password.require' => '账户密码不能为空',
        'password.confirm' => '账户密码两次不一致',
        'passwords.min'     => '账户密码最少5个字符',
		'passwords.max'     => '账户密码最大50个字符',
        'passwords.require' => '账户密码不能为空',
    ];

    /**
     * 应用场景：场景使用
     */
    protected $scene = [
        'register'  =>  ['password','email'],
        'login'     =>  ['passwords','username'],
    ];
}
