<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Message\LogSearchAnalyticsMessage;
use App\Service\AnalyticsCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unit tests for AnalyticsCollector service.
 * Tests validation logic and message dispatching.
 */
final class AnalyticsCollectorTest extends TestCase
{
    private MessageBusInterface $messageBus;

    private AnalyticsCollector $analyticsCollector;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->analyticsCollector = new AnalyticsCollector($this->messageBus);
    }

    public function testLogSearchDispatchesMessageForValidQuery(): void
    {
        $request = $this->createMockRequest('test query');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(LogSearchAnalyticsMessage::class))
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'test query',
            'hybrid_ai',
            10,
            150
        );
    }

    public function testLogSearchDoesNotLogEmptyQuery(): void
    {
        $request = $this->createMockRequest('');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->analyticsCollector->logSearch(
            $request,
            '',
            'hybrid_ai',
            0,
            50
        );
    }

    public function testLogSearchDoesNotLogOnlySpaces(): void
    {
        $request = $this->createMockRequest('   ');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->analyticsCollector->logSearch(
            $request,
            '   ',
            'hybrid_ai',
            0,
            50
        );
    }

    public function testLogSearchDoesNotLogEmptyQuotes(): void
    {
        $request = $this->createMockRequest('""');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->analyticsCollector->logSearch(
            $request,
            '""',
            'exact',
            0,
            50
        );
    }

    public function testLogSearchDoesNotLogEmptySingleQuotes(): void
    {
        $request = $this->createMockRequest("''");

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->analyticsCollector->logSearch(
            $request,
            "''",
            'exact',
            0,
            50
        );
    }

    public function testLogSearchAcceptsValidQueries(): void
    {
        $request = $this->createMockRequest('java');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'java',
            'hybrid_ai',
            15,
            120
        );
    }

    public function testLogSearchAcceptsWildcardQueries(): void
    {
        $request = $this->createMockRequest('java*');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'java*',
            'prefix',
            8,
            95
        );
    }

    public function testLogSearchAcceptsQuestionMarkWildcard(): void
    {
        $request = $this->createMockRequest('te?t');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'te?t',
            'prefix',
            5,
            80
        );
    }

    public function testLogSearchAcceptsQuotedPhrases(): void
    {
        $request = $this->createMockRequest('"artificial intelligence"');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            '"artificial intelligence"',
            'exact',
            3,
            110
        );
    }

    public function testLogSearchAcceptsMultiWordQueries(): void
    {
        $request = $this->createMockRequest('machine learning algorithms');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'machine learning algorithms',
            'hybrid_ai',
            25,
            200
        );
    }

    public function testLogSearchHandlesSpecialCharacters(): void
    {
        $request = $this->createMockRequest('C++ programming');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'C++ programming',
            'hybrid_ai',
            12,
            130
        );
    }

    public function testLogSearchHandlesUnicodeCharacters(): void
    {
        $request = $this->createMockRequest('inteligencia artificial');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'inteligencia artificial',
            'hybrid_ai',
            8,
            140
        );
    }

    public function testLogSearchCapturesSessionId(): void
    {
        $request = $this->createMockRequest('test');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (LogSearchAnalyticsMessage $message) {
                $data = $message->getData();

                return isset($data['session_id']) && $data['session_id'] === 'test-session-id';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'test',
            'hybrid_ai',
            5,
            100
        );
    }

    public function testLogSearchCapturesAllMetrics(): void
    {
        $request = $this->createMockRequest('complete test');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (LogSearchAnalyticsMessage $message) {
                $data = $message->getData();

                return $data['query'] === 'complete test'
                    && $data['search_strategy'] === 'hybrid_ai'
                    && $data['results_count'] === 42
                    && $data['response_time_ms'] === 250
                    && array_key_exists('user_ip', $data) // Use array_key_exists instead of isset (allows null)
                    && isset($data['user_agent'])
                    && isset($data['referer']);
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->analyticsCollector->logSearch(
            $request,
            'complete test',
            'hybrid_ai',
            42,
            250
        );
    }

    /**
     * Create a mock Request with session and headers.
     */
    private function createMockRequest(string $query): Request
    {
        $request = new Request();

        // Mock session
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('test-session-id');
        $request->setSession($session);

        // Mock headers
        $headers = $this->createMock(HeaderBag::class);
        $headers->method('get')->willReturnCallback(static function ($key) {
            return match ($key) {
                'User-Agent' => 'PHPUnit Test Browser',
                'Referer' => 'http://localhost/test',
                default => null,
            };
        });

        // Use reflection to inject the mock headers
        $reflection = new \ReflectionProperty(Request::class, 'headers');
        $reflection->setAccessible(true);
        $reflection->setValue($request, $headers);

        return $request;
    }
}
