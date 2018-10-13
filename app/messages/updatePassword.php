<?php
return array(
    'oldPassword' => array(
        'not_empty' => '请输入原密码',
        'regex' => '原密码长度在6~18位'
    ),
    'newPassword' => array(
        'not_empty' => '请输入新密码',
        'regex' => '原密码长度在6~18位'
    ),
    'rePassword' => array(
        'not_empty' => '请再次输入新密码',
        'regex' => '原密码长度在6~18位',
        'matches' => '两次密码不一样'
    )
);
