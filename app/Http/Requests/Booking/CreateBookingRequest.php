<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "user_id" => 'required',
            'doctor_id' => 'required',
            'appointment_date' => 'required',
            'time_from' => 'required',
            'time_to' => 'required',
        ];
    }

    public function messages()
    {
        return [
            "user_id.required" => "Please enter major name",
            "doctor_id.required" => "Please choose doctor",
            "appointment_date.required" => "Please enter appointment date",
            "time_from.required" => "Please choose time start",
            "time_to.required" => "Please choose time end",
        ];
    }
}
