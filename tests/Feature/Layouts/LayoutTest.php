<?php

namespace Tests\Feature\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Checks the shared layouts and partials (step 1 of the front end).
 * Each test renders a tiny page that uses a layout, then checks what ends up in the HTML.
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** Render a small test page inside a layout. */
    private function renderInLayout(string $layout): string
    {
        return Blade::render(
            "@extends('{$layout}') @section('title', 'Test page') @section('content')<p>PAGE BODY</p>@endsection"
        );
    }

    // ---------------------------------------------------------------
    // Public files
    // ---------------------------------------------------------------

    public function test_stylesheet_and_logo_files_exist(): void
    {
        $this->assertFileExists(public_path('css/h4r.css'));
        $this->assertFileExists(public_path('images/logo.svg'));
    }


    // ---------------------------------------------------------------
    // Account pages layout
    // ---------------------------------------------------------------

    public function test_auth_layout_has_logo_styles_title_and_content(): void
    {
        $html = $this->renderInLayout('layouts.auth');

        $this->assertStringContainsString('<title>Test page · H4R</title>', $html);
        $this->assertStringContainsString(asset('css/h4r.css'), $html);
        $this->assertStringContainsString('aria-label="H4R Property Management logo"', $html);
        $this->assertStringContainsString('<p>PAGE BODY</p>', $html);
    }

    // ---------------------------------------------------------------
    // Staff portal layout (side menu)
    // ---------------------------------------------------------------

    public function test_admin_portal_shows_user_logout_and_staff_link(): void
    {
        $admin = User::factory()->role('system_admin')->create(['name' => 'Test Admin']);
        $this->actingAs($admin);

        $html = $this->renderInLayout('layouts.portal');

        $this->assertStringContainsString('Test Admin', $html);
        $this->assertStringContainsString('aria-label="H4R"', $html);
        $this->assertStringContainsString('action="' . route('logout') . '"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('href="' . route('admin.dashboard') . '"', $html);
        $this->assertStringContainsString('href="' . route('admin.staff.index') . '"', $html);
        $this->assertStringContainsString('href="' . route('phone.verification.show') . '"', $html);
        $this->assertStringContainsString('<p>PAGE BODY</p>', $html);
    }

    public function test_manager_portal_has_no_admin_staff_link(): void
    {
        $this->actingAs(User::factory()->role('branch_manager')->create());

        $html = $this->renderInLayout('layouts.portal');

        $this->assertStringContainsString('href="' . route('manager.dashboard') . '"', $html);
        $this->assertStringNotContainsString(route('admin.staff.index'), $html);
    }

    public function test_specialist_and_receptionist_portals_link_to_staff_dashboard(): void
    {
        foreach (['property_specialist', 'receptionist'] as $role) {
            $this->actingAs(User::factory()->role($role)->create());

            $html = $this->renderInLayout('layouts.portal');

            $this->assertStringContainsString('href="' . route('staff.dashboard') . '"', $html, $role);
            $this->assertStringNotContainsString(route('admin.staff.index'), $html, $role);
        }
    }

    // ---------------------------------------------------------------
    // Owner and client layout (top menu)
    // ---------------------------------------------------------------

    public function test_owner_and_client_home_links_go_to_their_own_dashboard(): void
    {
        foreach (['owner' => 'owner.dashboard', 'client' => 'client.dashboard'] as $role => $dashboard) {
            $this->actingAs(User::factory()->role($role)->create());

            $html = $this->renderInLayout('layouts.portal-top');

            $this->assertStringContainsString('href="' . route($dashboard) . '"', $html, $role);
            $this->assertStringContainsString('href="' . route('phone.verification.show') . '"', $html, $role);
            $this->assertStringContainsString('action="' . route('logout') . '"', $html, $role);
            $this->assertStringContainsString('<p>PAGE BODY</p>', $html, $role);
        }
    }

    // ---------------------------------------------------------------
    // Success message
    // ---------------------------------------------------------------

    public function test_status_message_shows_only_when_set(): void
    {
        $this->actingAs(User::factory()->role('client')->create());

        $this->assertStringNotContainsString('role="status"', $this->renderInLayout('layouts.portal-top'));

        session(['status' => 'Phone verified.']);

        $html = $this->renderInLayout('layouts.portal-top');
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('Phone verified.', $html);
    }

    public function test_status_message_is_escaped(): void
    {
        $this->actingAs(User::factory()->role('client')->create());
        session(['status' => '<script>alert(1)</script>']);

        $html = $this->renderInLayout('layouts.portal-top');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
