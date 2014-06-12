<?php

class Test_Util
{
  // $class_obj : テストするクラスのオブジェクト(new CLASS_NAME())
  // $method_name : 呼び出すメソッド名のstring
  // $args : 呼び出すメソッドの引数
  //    array('args1' => 'hogehoge', 'args2' => 'fugafuga', ...)
  public static function invokeStaticMethod($class_obj, $method_name, $args)
  {
    $test_class = new ReflectionClass($class_obj);
    $method = $test_class->getMethod($method_name);
    // メソッドを外からアクセス可能にする
    $method->setAccessible(true);
    // invokeArgsメソッドの第一引数をnullにするとstaticメソッドを呼び出せる
    return $method->invokeArgs(null, $args);
  }
}

require_once dirname(dirname(__FILE__)) . '/includes/RakutenTag.php';
require_once dirname(dirname(__FILE__)) . '/includes/RakutenTagAdmin.php';

