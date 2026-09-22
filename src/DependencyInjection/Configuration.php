<?php

namespace Sendgo\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Sendgo 번들 설정 트리 정의.
 *
 * config/packages/sendgo.yaml 의 sendgo 키 하위 설정을 검증합니다.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sendgo');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('agent_token')->defaultNull()->end()
                // Sendgo 액세스 키 (발송 시 필수)
                ->scalarNode('access_key')
                    ->defaultNull()
                ->end()
                // Sendgo 시크릿 키 (발송 시 필수)
                ->scalarNode('secret_key')
                    ->defaultNull()
                ->end()
                // 카카오 발신프로필 키 (선택)
                ->scalarNode('kakao_sender_key')
                    ->defaultNull()
                ->end()
                // SMS 발신자 키 (선택)
                ->scalarNode('sms_sender_key')
                    ->defaultNull()
                ->end()
                // API 버전
                ->scalarNode('api_version')
                    ->defaultValue('v2')
                ->end()
                // API 기본 URL
                ->scalarNode('url')
                    ->defaultValue('https://sendgo.io')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
