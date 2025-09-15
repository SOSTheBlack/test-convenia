<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDate implements ValidationRule
{
    /**
     * @var string
     */
    private string $format;

    /**
     * Create a new rule instance.
     *
     * @param string $format O formato da data esperado (ex: Y-m-d, d/m/Y)
     */
    public function __construct(string $format = 'Y-m-d')
    {
        $this->format = $format;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Tenta criar um objeto Carbon com a data fornecida
        try {
            $date = Carbon::createFromFormat($this->format, $value);

            // Verifica se a data é válida no calendário
            if (!$date || $date->format($this->format) !== $value) {
                $fail('O campo :attribute contém uma data que não existe no calendário.');
            }
        } catch (\Exception $e) {
            $fail('O campo :attribute não corresponde ao formato ' . $this->format);
        }
    }
}
