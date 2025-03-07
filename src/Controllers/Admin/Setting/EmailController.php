<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Setting;

use App\Controllers\BaseController;
use App\Models\Setting;
use App\Services\Mail;

final class EmailController extends BaseController
{
    public static $update_field = [
        'mail_driver',
        // SMTP
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_port',
        'smtp_name',
        'smtp_sender',
        'smtp_ssl',
        'smtp_bbc',
        // Mailgun
        'mailgun_key',
        'mailgun_domain',
        'mailgun_sender',
        // Sendgrid
        'sendgrid_key',
        'sendgrid_sender',
        'sendgrid_name',
        // AWS SES
        'aws_access_key_id',
        'aws_secret_access_key',
        'aws_region',
        'aws_ses_sender',
        // Postal
        'postal_host',
        'postal_key',
        'postal_sender',
        'postal_name',
    ];

    public function email($request, $response, $args)
    {
        $settings = [];
        $settings_raw = Setting::get(['item', 'value', 'type']);

        foreach ($settings_raw as $setting) {
            if ($setting->type === 'bool') {
                $settings[$setting->item] = (bool) $setting->value;
            } else {
                $settings[$setting->item] = (string) $setting->value;
            }
        }

        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->assign('settings', $settings)
                ->fetch('admin/setting/email.tpl')
        );
    }

    public function saveEmail($request, $response, $args)
    {
        $list = self::$update_field;

        foreach ($list as $item) {
            $setting = Setting::where('item', '=', $item)->first();

            if ($setting->type === 'array') {
                $setting->value = \json_encode($request->getParam("${item}"));
            } else {
                $setting->value = $request->getParam("${item}");
            }

            if (! $setting->save()) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => "保存 ${item} 时出错",
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '保存成功',
        ]);
    }

    public function testEmail($request, $response, $args)
    {
        $to = $request->getParam('recipient');

        try {
            Mail::send(
                $to,
                '测试邮件',
                'auth/test.tpl',
                [],
                []
            );
        } catch (\Throwable $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '测试邮件发送失败',
            ]);
        }
        return $response->withJson([
            'ret' => 1,
            'msg' => '测试邮件发送成功',
        ]);
    }
}
