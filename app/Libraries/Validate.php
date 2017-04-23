<?php

namespace App\Libraries;
/**
 * Validate class
 * @package validator
 * @author Prasetya Setiawan
 * Available rule required, numeric
 */

class Validate {
    protected $multiple = FALSE;
    protected $current_iteration = 0;

    public function Execute($param, $rules)
    {
        $message = [];
        foreach($rules as $index => $a)
        {
            $list_rule = explode('|', $a);

            //check if array
            if(is_array($param[$index]))
            {
                $this->multiple = TRUE;
                $count = count($param[$index]);
                for($i = 0; $i < $count; $i++)
                {
                    $this->current_iteration = $i;
                    $message = $this->startValidation($list_rule, $param, $index);
                    if(!empty($message))
                    {
                        break;
                    }
                }
            }
            else
            {
                $error_message = $this->startValidation($list_rule, $param, $index);
                if(!empty($error_message))
                {
                    $message[$index] = $error_message;
                }
            }
        }

        if(!empty($message))
        {
            $result['status'] = 0;
            $result['message'] = $message;
        }
        else
        {
            $result['status'] = 1;
        }

        return $result;
    }

    private function startValidation($list_rule, $param, $index)
    {
        $message = '';

        foreach($list_rule as $b)
        {
            $break = explode(':', $b);
            $function = $break[0];

            $valid = $this->actionValidation($param, $function, $break, $index);

            if(!$valid)
            {
                $message = 'Invalid Parameter';
            }
        }

        return $message;
    }

    private function actionValidation($param, $function, $break, $index)
    {
        $need_validate = TRUE;

        if(isset($break[1]))
        {
            //value validation
            $valid = $this->valueValidation($param, $function, $break, $index);

            $need_validate = FALSE;
        }

        //parameter validation
        if($need_validate)
        {
            $valid = $this->$function($param, $index);
        }

        return $valid;
    }

    private function valueValidation($param, $function, $break, $index)
    {
        if($this->multiple)
        {
            $variable = $param[$index][$this->current_iteration];
        }
        else
        {
            if(isset($param[$index]))
            {
                $variable = $param[$index];
            }
        }

        if($function == 'in')
        {
            if(isset($param[$index]))
            {
                $list_item = explode(',', $break[1]);
                $valid = $this->in($variable, $list_item);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif($function == 'required_if')
        {
            $detail = explode(',', $break[1]);
            $required_index = $detail[0];
            $required_value = $detail[1];

            if(isset($param[$required_index]))
            {
                $valid = $this->required_if($param, $index, $required_index, $required_value);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif(isset($break[1]) && $function == 'max')
        {
            if(isset($param[$index]))
            {
                $valid = $this->max($variable, $break[1]);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif($function == 'min')
        {
            if(isset($param[$index]))
            {
                $valid = $this->min($variable, $break[1]);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif($function == 'valid_date')
        {
            if(isset($param[$index]))
            {
                $valid = $this->valid_date($variable);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif($function == 'extension')
        {
            if(isset($param[$index]))
            {
                $list_item = explode(',', $break[1]);

                $valid = $this->extension($variable, $list_item);
            }
            else
            {
                $valid = TRUE;
            }
        }
        elseif($function == 'maxSize')
        {
            if (isset($param[$index]))
            {
                $valid = $this->maxSize($variable, $break[1]);
            }
            else
            {
                $valid = TRUE;
            }
        }

        return $valid;
    }
    
    private function required($param, $index)
    {
        if(isset($param[$index]))
        {
            if($this->multiple)
            {
                if(isset($param[$index][$this->current_iteration]))
                {
                    return TRUE;
                }
                else
                {
                    return FALSE;
                }
            }

            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    private function required_if($param, $index, $required_index, $required_value)
    {
        if(isset($param[$required_index]))
        {
            if($param[$required_index] == $required_value)
            {
                return $this->required($param, $index);
            }
            else
            {
                return TRUE;
            }
        }
        else
        {
            return TRUE;
        }
    }
    
    private function numeric($param, $index)
    {
        if(isset($param[$index]))
        {
            if($this->multiple)
            {
                if(is_numeric($param[$index][$this->current_iteration]))
                {
                    return TRUE;
                }
                else
                {
                    return FALSE;
                }
            }
            else
            {
                if(is_numeric($param[$index]))
                {
                    return TRUE;
                }
                else
                {
                    return FALSE;
                }
            }
        }
        else
        {
            return TRUE;
        }
    }
	
    private function in($variable, $list_item)
    {
        $n_item = count($list_item);
        for($i = 0; $i < $n_item; $i++)
        {
            //if parameter request match it with the list rules
            if($this->match($variable, $list_item[$i]))
            {
                return TRUE;
                break;
            }
        }

        return FALSE;
    }

    private function match($expected_value, $value)
    {
        if($expected_value == $value)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    private function max($variable, $value)
    {
        if($variable > $value)
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    private function min($variable, $value)
    {
        if($variable < $value)
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
	
    private function valid_date($param)
    {
        $format_date = explode('-', $param);
        $date = (isset($format_date[2]) ? $format_date[2] : 99);
        $month = (isset($format_date[1]) ? $format_date[1] : 99);
        $year = (isset($format_date[0]) ? $format_date[0] : 99);

        return checkdate($month, $date, $year);
    }

    public function file($param, $index)
    {
        if($this->multiple)
        {
            $variable = $param[$index][$this->current_iteration];
        }
        else
        {
            $variable = $param[$index];
        }

        if($variable->isValid())
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    public function extension($variable, $list_item)
    {
        $n_item = count($list_item);
        for($i = 0; $i < $n_item; $i++)
        {
            //if parameter request match it with the list rules
            if($this->match($variable->extension(), $list_item[$i]))
            {
                return TRUE;
                break;
            }
        }

        return FALSE;
    }

    public function maxSize($variable, $size)
    {
        if($variable->getSize() <= $size)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}
