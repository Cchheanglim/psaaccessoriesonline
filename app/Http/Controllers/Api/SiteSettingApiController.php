<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Storefront;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Staff portal > Website: the header, home page, footer, social accounts (needs "Edit products & shop")
 * and the shop rules: delivery fee and membership amounts (admins only).
 */
class SiteSettingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->staff($request);

        return response()->json($this->payload($user));
    }

    /** {settings: {key: value}}; an empty value puts the original text back (except optional fields, which hide). */
    public function update(Request $request): JsonResponse
    {
        $user = $this->staff($request);
        $data = $request->validate(['settings' => ['required', 'array', 'min:1']]);

        $values = [];
        $errors = [];
        foreach ($data['settings'] as $key => $value) {
            $field = SiteSetting::FIELDS[$key] ?? null;
            if (! $field) {
                $errors["settings.{$key}"] = 'This setting does not exist.';
                continue;
            }
            [$group, $label, $original, $limit, $type] = $field;
            if ($group === 'rules' && ! $user->isAdmin()) {
                abort(403, 'Only an Admin can change the delivery fee and membership rules.');
            }
            if ($value !== null && ! is_scalar($value)) {
                $errors["settings.{$key}"] = "{$label} must be text.";
                continue;
            }
            $value = $value === null ? null : trim(preg_replace('/[ \t]+/', ' ', (string) $value));

            if ($value === null || $value === '') {
                // Optional pieces (empty original: phone, email...) and things staff may hide (messages, chips,
                // social accounts) can be empty; the rest go back to their original text.
                $values[$key] = ($original === '' || $type === 'handle' || preg_match('/announcement|chip/', $key)) ? '' : null;
                continue;
            }

            if (is_array($limit)) { // numbers
                if (! is_numeric($value) || (float) $value < $limit[0] || (float) $value > $limit[1]) {
                    $errors["settings.{$key}"] = "{$label} must be a number from {$limit[0]} to {$limit[1]}.";
                    continue;
                }
                $value = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
            } elseif ($type === 'image') {
                if (! preg_match('#^(data:image/(png|jpe?g|webp|gif|svg\+xml);base64,[A-Za-z0-9+/=]+|assets/images/[\w./-]+|https://\S+)$#', $value)) {
                    $errors["settings.{$key}"] = 'Choose a picture file (PNG, JPG, WebP or SVG).';
                    continue;
                }
                if (strlen($value) > $limit) {
                    $errors["settings.{$key}"] = 'That picture is too big. Use one under about 400 KB.';
                    continue;
                }
            } elseif (mb_strlen($value) > $limit) {
                $errors["settings.{$key}"] = "Keep {$label} to {$limit} characters.";
                continue;
            } elseif ($type === 'handle' && ! preg_match('/^@?[\w.\-]{1,59}$/u', $value)) {
                $errors["settings.{$key}"] = "{$label}: just the account name, like @psaonline (no link, no spaces).";
                continue;
            }
            if ($key === 'footer.email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors["settings.{$key}"] = 'Enter an email address like hello@psaonline.store.';
                continue;
            }
            $values[$key] = $value;
        }

        // Max has to need more spending than Pro.
        $pro = (float) ($values['membership.pro_min_spend_usd'] ?? SiteSetting::get('membership.pro_min_spend_usd'));
        $max = (float) ($values['membership.max_min_spend_usd'] ?? SiteSetting::get('membership.max_min_spend_usd'));
        if ((isset($values['membership.pro_min_spend_usd']) || isset($values['membership.max_min_spend_usd'])) && $max <= $pro) {
            $errors['settings.membership.max_min_spend_usd'] = 'Max must need more spending than Pro.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        SiteSetting::putMany($values);
        Storefront::forgetCatalog();

        return response()->json($this->payload($user));
    }

    private function payload(User $user): array
    {
        $values = SiteSetting::map();

        return [
            'canEditRules' => $user->isAdmin(),
            'fields' => collect(SiteSetting::FIELDS)->map(fn ($f, $key) => [
                'key' => $key,
                'group' => $f[0],
                'label' => $f[1],
                'original' => $f[2],
                'max' => is_array($f[3]) ? null : $f[3],
                'min' => is_array($f[3]) ? $f[3][0] : null,
                'maxValue' => is_array($f[3]) ? $f[3][1] : null,
                'type' => $f[4],
                'hint' => $f[5],
                'value' => $values[$key],
            ])->values()->all(),
        ];
    }

    private function staff(Request $request): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($user->isStaff() && $user->status !== 'Suspended' && $user->hasPermission('manage_products'), 403,
            'Your role does not have the "Edit products & shop" permission.');

        return $user;
    }
}
