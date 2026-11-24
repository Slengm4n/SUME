<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validação de formulários com mensagens em português.
 *
 *   $v = new Validator($_POST);
 *   $v->required('name', 'Informe seu nome.')->email('email');
 *   if ($v->fails()) { ... $v->errors() ... }
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    public function required(string $field, string $message = 'Campo obrigatório.'): self
    {
        if ($this->value($field) === '') {
            $this->add($field, $message);
        }

        return $this;
    }

    public function max(string $field, int $length, ?string $message = null): self
    {
        if (mb_strlen($this->value($field)) > $length) {
            $this->add($field, $message ?? "Use no máximo {$length} caracteres.");
        }

        return $this;
    }

    public function email(string $field, string $message = 'Informe um e-mail válido.'): self
    {
        $value = $this->value($field);
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function password(string $field): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (strlen($value) < 8) {
            $this->add($field, 'A senha precisa ter pelo menos 8 caracteres.');
        } elseif (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
            $this->add($field, 'A senha precisa ter letras e números.');
        }

        return $this;
    }

    public function same(string $field, string $other, string $message = 'Os valores não conferem.'): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$other] ?? null)) {
            $this->add($other, $message);
        }

        return $this;
    }

    public function digits(string $field, string $message = 'Use apenas números.'): self
    {
        $value = $this->value($field);
        if ($value !== '' && !ctype_digit($value)) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function date(string $field, string $message = 'Data inválida.'): self
    {
        $value = $this->value($field);
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->add($field, $message);
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

        return $this;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
