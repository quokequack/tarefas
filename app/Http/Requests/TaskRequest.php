<?php

namespace App\Http\Requests;

use App\Services\RequestTrace;
use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        app(RequestTrace::class)->record('request', __FUNCTION__, 'A Form Request aplica required, string e max:255 antes de executar a ação.');

        return ['title' => ['required', 'string', 'max:255']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título da tarefa.',
            'title.string' => 'O título deve ser um texto.',
            'title.max' => 'O título deve ter no máximo 255 caracteres.',
        ];
    }
}
