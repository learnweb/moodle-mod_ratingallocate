<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_ratingallocate\local\lp;

class linear_program {
    const MAXIMUM_LINE_INPUT = 510;
    const MAXIMUM_NAME_LENGTH = 255;

    const NONE = -1;
    const MINIMIZE = 0;
    const MAXIMIZE = 1;

    const BINARY = 0;
    const INTEGER = 1;
    const REAL = 2;
    const COMPLEX = 3;

    private $objective_method = self::NONE;
    private $objective_function = '';

    private $constraints = [];
    private $bounds = [];
    private $variables = [];

    public function set_objective_method($objective_method) {
        $this->objective_method = $objective_method;
    }

    public function get_objective_method() {
        return $this->objective_method;
    }

    public function set_objective_function($objective_function) {
        $this->objective_function = $objective_function;
    }

    public function get_objective_function() {
        return $this->objective_function;
    }

    public function set_objective($objective_method, $objective_function) {
        $this->set_objective_method($objective_method);
        $this->set_objective_function($objective_function);
    }

    public function set_constraints($constraints) {
        $this->constraints = $constraints;
    }

    public function get_constraints() {
        return $this->constraints;
    }

    public function add_constraint($constraint) {
        $this->constraints[] = $constraint;
    }

    public function set_bounds($bounds) {
        $this->bounds = $bounds;
    }

    public function get_bounds() {
        return $this->bounds;
    }

    public function add_bound($bound) {
        $this->bounds[] = $bound;
    }

    public function set_variables($variables) {
        $this->variables = $variables;
    }

    public function get_variables() {
        return $this->variables;
    }

    public function get_variable_names() {
        return array_map(function($x) {
            return $x['name'];
        }, $this->variables);
    }

    public function add_variable($variable, $type = self::REAL) {
        if(strlen($variable) > self::MAXIMUM_NAME_LENGTH)
            throw new \exception('Name length exceeds the maximum!');

        $this->variables[$variable] = ['type' => $type, 'name' => $variable];
    }

    public function write_objective_method() {
        if($this->get_objective_method() == linear_program::MINIMIZE)
            return 'Minimize';
        elseif($this->get_objective_method() == linear_program::MAXIMIZE)
            return 'Maximize';

        throw new \exception('Linear program objectives method is invalid!');
    }

    public function write_objective() {
        if(empty($this->get_objective_function()))
            throw new \exception('Linear program objectives function is invalid!');

        return $this->write_objective_method($this->get_objective_method())."\n".$this->prepare_term($this->get_objective_function())."\n";
    }

    public function write_constraints() {
        if(empty($this->get_constraints()))
            return '';

        return "Subject To\n".implode(array_map(function($constraint) {
            return $this->prepare_term($constraint)."\n";
        }, $this->get_constraints()));
    }

    public function write_bounds() {
        if(empty($this->bounds))
            return '';

        return "Bounds\n".implode(array_map(function($bound) {
            return $this->prepare_term($bound)."\n";
        }, $this->get_bounds()));
    }

    public function write_variables() {
        if(empty($this->get_variables()))
            return '';

        return "General\n".$this->prepare_string(array_map(function($x) { return $x['name'].' '; }, $this->get_variables()))."\n";
    }

    public function write() {
        return $this->write_objective()
            .$this->write_constraints()
            .$this->write_bounds()
            .$this->write_variables().'End';
    }

    public function prepare_term($term) {
        return $this->prepare_string(preg_split('$([\+\-])$', str_replace(['*', ' '], '', $term), null, PREG_SPLIT_DELIM_CAPTURE));
    }

    public function prepare_string($array) {
        $line_size = 0;

        return array_reduce($array, function($carry, $x) use (&$line_size) {
            $x_length = strlen($x);

            if(($line_size + $x_length + 1) > self::MAXIMUM_LINE_INPUT) {
                $line_size = 0;
                $x = "\n".$x;
                $carry = trim($carry);
            }

            $line_size += $x_length;
            return $carry.$x;
        }, '');
    }
};