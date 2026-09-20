<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Http\Requests\Request;

class EventRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(GateContract $gate)
    {
//        return $gate->allows('create-event');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return
        [
            'name' => 'required',
            'photo' => 'mimes:jpeg,gif,png',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'timegrid_start_hour' => 'nullable|integer|between:0,23',
            'timegrid_end_hour' => 'nullable|integer|between:1,24',
        ];
    }

    /**
     * Make sure a manual time grid ends after it starts.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function($validator)
        {
            $start = $this->input('timegrid_start_hour');
            $end = $this->input('timegrid_end_hour');

            if($start !== null && $start !== '' && $end !== null && $end !== '' && (int)$end <= (int)$start)
            {
                $validator->errors()->add('timegrid_end_hour', 'The time grid has to end after it starts.');
            }
        });
    }
}
