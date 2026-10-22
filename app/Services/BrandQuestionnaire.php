<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BrandQuestionnaire
{
    public function questions(): array
    {
        return collect(config('brand_onboarding.questions'))->map(function ($question) {
            if (isset($question['yesno'])) {
                $question['options'] = ['yes' => 'Oui', 'no' => 'Non', 'unknown' => 'Je ne sais pas encore'];
            }

            return $question;
        })->all();
    }

    public function visible(array $question, array $answers): bool
    {
        if (! isset($question['when'])) {
            return true;
        }
        [$parent, $value] = $question['when'];

        return ($answers[$parent] ?? null) === $value && $this->visible($this->questions()[$parent], $answers);
    }

    public function validate(array $answers): array
    {
        $rules = [];
        $clean = [];
        $names = [];
        foreach ($this->questions() as $key => $question) {
            if (! $this->visible($question, $answers)) {
                continue;
            }
            $clean[$key] = $answers[$key] ?? null;
            $rules[$key] = isset($question['options'])
                ? ['required', Rule::in(array_keys($question['options']))]
                : (($question['type'] ?? '') === 'number' ? ['required', 'numeric', 'min:0', 'max:100'] : ['required', 'string', 'max:3000']);
            $names[$key] = $question['label'];
        }

        return Validator::make($clean, $rules, [], $names)->validate();
    }
}
