<?php
require __DIR__.'/../vendor/autoload.php';
use Sendgo\Php\AccountClient;
use Sendgo\Php\Sendgo;
use Sendgo\Symfony\DependencyInjection\SendgoExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// 발송용 키 없이 계정 클라이언트를 만들 수 있어야 합니다.
$container = new ContainerBuilder();
(new SendgoExtension())->load([['agent_token' => 'test-agent']], $container);
$container->compile();
if (!$container->get(AccountClient::class) instanceof AccountClient || $container->has(Sendgo::class)) {
    throw new RuntimeException('계정 전용 구성 실패');
}
// 기존 발송용 설정은 그대로 동작해야 합니다.
$container = new ContainerBuilder();
(new SendgoExtension())->load([['access_key' => 'test-access', 'secret_key' => 'test-secret']], $container);
$container->compile();
if (!$container->get(Sendgo::class) instanceof Sendgo || $container->has(AccountClient::class)) {
    throw new RuntimeException('기존 발송 구성 실패');
}
