<?php

namespace Sendgo\Symfony\DependencyInjection;

use Sendgo\Php\Sendgo;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Sendgo 번들 확장.
 *
 * 설정값을 읽어 Sendgo\Php\Sendgo 서비스를 컨테이너에 등록하고,
 * 'sendgo' 별칭을 추가하여 타입 기반 오토와이어링을 지원합니다.
 */
class SendgoExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // 설정 배열을 코어 SDK 생성자 인자로 매핑 (snake_case 키 유지)
        $definition = new Definition(Sendgo::class, [
            [
                'access_key'       => $config['access_key'],
                'secret_key'       => $config['secret_key'],
                'kakao_sender_key' => $config['kakao_sender_key'],
                'sms_sender_key'   => $config['sms_sender_key'],
                'api_version'      => $config['api_version'],
                'url'              => $config['url'],
            ],
        ]);
        $definition->setPublic(true);

        $container->setDefinition(Sendgo::class, $definition);

        // 'sendgo' 별칭 등록 (오토와이어링 및 서비스 ID 접근용)
        $alias = $container->setAlias('sendgo', Sendgo::class);
        $alias->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'sendgo';
    }
}
