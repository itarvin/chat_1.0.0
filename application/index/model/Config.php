<?php
namespace app\index\model;
use think\Model;
/**
 * 应用场景：配置模型类
 * @since   2018/06/19 创建
 * @author  itarvin itarvin@gmail.com
 */
class Config extends Model{
    // 直接使用配置参数名
    protected $connection = 'db_config1';
}
