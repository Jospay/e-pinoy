<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\UserManager;

class ManagerWithoutBranch implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $manager = UserManager::find($value);

        if ($manager && $manager->branches()->exists()) {
            $fail('This manager is already assigned to a branch.');
        }
    }
}
