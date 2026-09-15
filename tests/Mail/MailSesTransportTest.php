<?php

namespace Heritage\Tests\Mail;

use Aws\Command;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\Ses\SesClient;
use Heritage\Config\Repository;
use Heritage\Container\Container;
use Heritage\Mail\MailManager;
use Heritage\Mail\Transport\SesTransport;
use Heritage\View\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSesTransportTest extends TestCase
{
    public function testGetTransport(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services.ses' => [
                    'key' => 'foo',
                    'secret' => 'bar',
                    'region' => 'us-east-1',
                ],
            ]);
        });

        $manager = new MailManager($container);

        /** @var \Heritage\Mail\Transport\SesTransport $transport */
        $transport = $manager->createSymfonyTransport(['transport' => 'ses']);

        $ses = $transport->ses();

        $this->assertSame('us-east-1', $ses->getRegion());

        $this->assertSame('ses', (string) $transport);
    }

    public function testSend(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');
        $message->bcc('you@example.com');
        $message->replyTo(new Address('taylor@example.com', 'Taylor Otwell'));
        $message->getHeaders()->add(new MetadataHeader('FooTag', 'TagValue'));
        $message->getHeaders()->addTextHeader('X-Ses-List-Management-Options', 'contactListName=TestList;topicName=TestTopic');

        $mock = new MockHandler;
        $mock->append(new Result(['MessageId' => 'ses-message-id']));

        $client = new SesClient([
            'credentials' => new Credentials('foo', 'bar'),
            'region' => 'us-east-1',
            'version' => 'latest',
            'handler' => $mock,
        ]);

        (new SesTransport($client))->send($message);

        $arg = $mock->getLastCommand()->toArray();

        $this->assertSame('myself@example.com', $arg['Source']);
        $this->assertSame(['me@example.com', 'you@example.com'], $arg['Destinations']);
        $this->assertSame(['ContactListName' => 'TestList', 'TopicName' => 'TestTopic'], $arg['ListManagementOptions']);
        $this->assertSame([['Name' => 'FooTag', 'Value' => 'TagValue']], $arg['Tags']);
        $this->assertStringContainsString('Reply-To: Taylor Otwell <taylor@example.com>', $arg['RawMessage']['Data']);
    }

    public function testSendError(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');

        $mock = new MockHandler;
        $mock->append(new AwsException('Email address is not verified.', new Command('sendRawEmail')));

        $client = new SesClient([
            'credentials' => new Credentials('foo', 'bar'),
            'region' => 'us-east-1',
            'version' => 'latest',
            'handler' => $mock,
        ]);

        $this->expectException(TransportException::class);

        (new SesTransport($client))->send($message);
    }

    public function testSesLocalConfiguration(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'mail' => [
                    'mailers' => [
                        'ses' => [
                            'transport' => 'ses',
                            'region' => 'eu-west-1',
                            'options' => [
                                'ConfigurationSetName' => 'Ugarit',
                                'Tags' => [
                                    ['Name' => 'Ugarit', 'Value' => 'Framework'],
                                ],
                            ],
                        ],
                    ],
                ],
                'services' => [
                    'ses' => [
                        'region' => 'us-east-1',
                    ],
                ],
            ]);
        });

        $container->instance('view', $this->createMock(Factory::class));

        $container->bind('events', function () {
            return null;
        });

        $manager = new MailManager($container);

        /** @var \Heritage\Mail\Mailer $mailer */
        $mailer = $manager->mailer('ses');

        /** @var \Heritage\Mail\Transport\SesTransport $transport */
        $transport = $mailer->getSymfonyTransport();

        $this->assertSame('eu-west-1', $transport->ses()->getRegion());

        $this->assertSame([
            'ConfigurationSetName' => 'Ugarit',
            'Tags' => [
                ['Name' => 'Ugarit', 'Value' => 'Framework'],
            ],
        ], $transport->getOptions());
    }
}
