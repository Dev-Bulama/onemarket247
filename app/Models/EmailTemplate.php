<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'subject', 'body', 'placeholders', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The row a notification should use for $key, or null when the admin
     * hasn't customized-and-activated one — callers fall back to their own
     * hardcoded copy in that case, so nothing changes until an admin opts in.
     */
    public static function active(string $key): ?self
    {
        return static::query()->where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Substitutes {{token}} placeholders in this template's subject/body
     * with the given values. Unmatched placeholders are left as-is rather
     * than erroring, since strtr() simply won't find a matching token.
     *
     * @param  array<string, string>  $replacements  keyed by bare token name, e.g. ['vendor_name' => 'Acme']
     * @return array{subject: string, body: string}
     */
    public function render(array $replacements): array
    {
        $map = [];

        foreach ($replacements as $token => $value) {
            $map['{{'.$token.'}}'] = $value;
        }

        return [
            'subject' => strtr($this->subject, $map),
            'body' => strtr($this->body, $map),
        ];
    }
}
