<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSelectionTest extends TestCase
{
    public function test_selected_locale_is_applied_to_subsequent_pages_and_renders_rtl_for_arabic(): void
    {
        $this->post(route('locale.update'), [
            'locale' => 'ar',
            'return_to' => '/forgot-password',
        ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('locale', 'ar')
            ->assertCookie('tourlink_locale', 'ar');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('إعادة تعيين كلمة المرور');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), [
                'locale' => 'zz',
                'return_to' => route('home'),
            ])
            ->assertSessionHasErrors('locale')
            ->assertSessionMissing('locale');
    }

    public function test_locale_change_does_not_redirect_to_an_external_host(): void
    {
        $this->post(route('locale.update'), [
            'locale' => 'fr',
            'return_to' => '//example.com',
        ])->assertRedirect('/');
    }
}
