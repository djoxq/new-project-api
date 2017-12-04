<?php

namespace App\Http\Requests\API;

use APIException;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class APIRequest extends Request
{
    /**
     * Custom validation messages for API errors status
     *
     * @return array
     */
    public function messages()
    {

        return [
            'required' => 'required',
            'required_with' => 'required',
            'required_without' => 'required',
            'min' => 'too_short',
            'max' => 'too_long',
            'email' => 'invalid_email',
            'date' => 'invalid_date',
            'json' => 'invalid_json',
            'image' => 'invalid_image',
            'numeric' => 'not_numeric',
            'exists' => 'not_found',
            'same' => 'not_same',
            'unique' => 'duplicated',
        ];
    }

    /**
     * Get the entities this request has to expand
     *
     * @return array
     */
    public function getExpanders()
    {
        $shouldExpand = explode(',', $this->input('expand'));
        return array_intersect($shouldExpand, $this->canExpand());
    }

    /**
     * Check if this request has to expand something
     *
     * @return bool
     */
    public function hasExpanders()
    {
        return !empty($this->getExpanders());
    }

    /**
     * Check if request has pagination details
     *
     * @return boolean
     */
    public function hasPagination()
    {
        return  !empty($this->input('page_size')) &&
            !empty($this->input('page_number'));
    }

    /**
     * Get pagination details
     *
     * @return array
     */
    public function getPagination()
    {
        return [
            'page_size' => intval($this->input('page_size')),
            'page_number' => intval($this->input('page_number'))
        ];
    }

    /**
     * Check if request has to sort something
     *
     * @return array
     */
    public function hasSorter()
    {
        return !empty($this->getSorter());
    }

    /**
     * Get sorter details
     *
     * @return bool
     */
    public function getSorter()
    {
        $sorter['sort_by'] = Str::snake($this->input('sort_by'));
        $sorter['sort_type'] = (empty($this->input('sort_desc')) || ($this->input('sort_desc') === 'false')) ? 'asc' : 'desc';
        return in_array($sorter['sort_by'], $this->canSort()) ? $sorter : null;
    }

    /**
     * Check if request has to filter something
     *
     * @return array
     */
    public function hasFilters()
    {
        return !empty($this->getFilters());
    }

    /**
     * Get filters details
     *
     * @return bool
     */
    public function getFilters()
    {
        $canFilter = $this->canFilter();
        $filters = [
            'where' => [],
            'where_in' => [],
            'where_not_in' => [],
            'where_between' => [],
            'where_not_between' => [],
        ];
        foreach ($canFilter as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (Str::contains($value, ',')) {
                    $filters['where_in'][$field] = explode(',', $value);
                } else {
                    $filters['where'][] = [
                        $field,
                        '=',
                        $value,
                    ];
                }
            }
            else if ($this->has($field . '><')) {
                $value = explode(',', $this->input($field . '><'));
                if (count($value == 2)) {
                    $filters['where_between'][$field] = $value;
                }
            }
            else if ($this->has($field . '<>')) {
                $value = explode(',', $this->input($field . '<>'));
                if (count($value == 2)) {
                    $filters['where_not_between'][$field] = $value;
                }
            }
            else if ($this->has($field . '<')) {
                $filters['where'][] = [
                    $field,
                    '<=',
                    $this->input($field . '<'),
                ];
            }
            else if ($this->has($field . '>')) {
                $filters['where'][] = [
                    $field,
                    '>=',
                    $this->input($field . '>'),
                ];
            }
            else if ($this->has($field . '!')) {
                $value = $this->input($field . '!');
                if (Str::contains($value, ',')) {
                    $filters['where_not_in'][$field] = explode(',', $value);
                } else {
                    $filters['where'][] = [
                        $field,
                        '<>',
                        $value,
                    ];
                }
            }
            else if ($this->has($field . '~')) {
                $value = $this->input($field . '~');
                $filters['where'][] = [
                    $field,
                    'like',
                    "%{$value}%",
                ];
            }
        }

        return $filters;
    }

    /**
     * Custom error messages format
     *
     * @param  Validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        return array_map(function($item) {
            return $item[0];
        }, $validator->errors()->messages());
    }

    /**
     * Custom behaviour if APIRequest validation failed
     *
     * @param  Validator
     * @return void
     */
    protected function failedValidation(Validator $validator) {
        throw new APIException(
            $this->formatErrors($validator),
            HttpResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Default function for expanders
     *
     * @return array
     */
    protected function canExpand()
    {
        return [
            //
        ];
    }

    /**
     * Default function for sorters
     *
     * @return array
     */
    protected function canSort()
    {
        return [
            //
        ];
    }

    /**
     * Default function for filters
     *
     * @return array
     */
    protected function canFilter()
    {
        return [
            //
        ];
    }
}
