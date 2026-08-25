<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class NavigationTtsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('framework/testing/navigation-tts-endpoint-'.bin2hex(random_bytes(4)));
        File::makeDirectory($this->testDirectory, 0755, true);
        $this->configureDependencies();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDirectory);

        parent::tearDown();
    }

    public function test_shipper_can_generate_navigation_audio(): void
    {
        Process::fake(function (PendingProcess $process) {
            $index = array_search('--output_file', $process->command, true);
            File::put($process->command[$index + 1], 'RIFF'.str_repeat("\0", 300));

            return Process::result();
        });
        Process::preventStrayProcesses();
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.navigation.voice'), ['text' => 'Còn 50 mét, rẽ trái.'])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav')
            ->assertHeader('X-Navigation-Voice', 'vi_VN-test');

        $this->assertSame([], File::glob($this->testDirectory.'/cache/*.tmp.wav'));
    }

    public function test_customer_and_staff_cannot_access_shipper_navigation_tts(): void
    {
        Process::fake();
        Process::preventStrayProcesses();
        $customer = User::factory()->create(['role_id' => 1]);
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);

        $this->actingAs($customer)
            ->postJson(route('shipper.navigation.voice'), ['text' => 'Đi thẳng.'])
            ->assertRedirect(route('home'));

        $this->actingAs($staff)
            ->postJson(route('shipper.navigation.voice'), ['text' => 'Đi thẳng.'])
            ->assertRedirect(route('home'));

        Process::assertNothingRan();
    }

    public function test_unavailable_piper_returns_safe_503_without_exposing_server_paths(): void
    {
        File::delete(config('services.navigation_tts.piper.model'));
        Process::fake();
        Process::preventStrayProcesses();
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $this->actingAs($shipper)
            ->postJson(route('shipper.navigation.voice'), ['text' => 'Đi thẳng.'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'tts_unavailable')
            ->assertJsonPath('success', false)
            ->assertJsonMissingPath('exception')
            ->assertDontSee($this->testDirectory);

        Process::assertNothingRan();
    }

    public function test_navigation_tts_route_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('shipper.navigation.voice');

        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('shipper', $route->gatherMiddleware());
        $this->assertContains('throttle:20,1', $route->gatherMiddleware());
    }

    private function configureDependencies(): void
    {
        $binary = $this->testDirectory.'/piper';
        $model = $this->testDirectory.'/voice.onnx';
        $modelConfig = $this->testDirectory.'/voice.onnx.json';

        File::put($binary, 'fake binary');
        File::put($model, 'fake model');
        File::put($modelConfig, '{}');

        config()->set('services.navigation_tts.enabled', true);
        config()->set('services.navigation_tts.driver', 'piper');
        config()->set('services.navigation_tts.piper.binary', $binary);
        config()->set('services.navigation_tts.piper.model', $model);
        config()->set('services.navigation_tts.piper.config', $modelConfig);
        config()->set('services.navigation_tts.piper.cache', $this->testDirectory.'/cache');
        config()->set('services.navigation_tts.piper.voice', 'vi_VN-test');
        config()->set('services.navigation_tts.piper.timeout', 12);
    }
}
