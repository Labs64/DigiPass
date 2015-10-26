<?php

namespace NetLicensing;

if (!class_exists('BaseEntity')) {

    abstract class BaseEntity
    {
        protected $_properties = array();
        protected $_old_properties = array();

        protected $_required_properties = array();

        public function __construct(array $properties = array())
        {
            $this->_setRequiredProperty('number');
            $this->_setRequiredProperty('active');

            $this->_setProperties($properties);

            $this->init($properties);
        }

        protected function init($properties)
        {

        }

        public function _update()
        {
            $this->_old_properties = $this->_properties;
        }

        public function isNew()
        {
            return ($this->_getOldProperties());
        }

        public function setNumber($number)
        {
            $this->_setProperty('number', $number);
        }

        public function getNumber()
        {
            return $this->_getProperty('number');
        }

        /**
         * @param bool|string $state
         */
        public function setActive($state)
        {
            if (is_bool($state)) $state = ($state) ? 'true' : 'false';

            $this->_setProperty('active', $state);
        }

        /**
         * @return bool
         */
        public function getActive()
        {
            return ($this->_getProperty('active'));
        }

        public function getProperty($name)
        {
            return $this->_getProperty($name);
        }

        public function setProperty($name, $value)
        {
            $this->_setProperty($name, $value);
        }

        public function setProperties(array $properties)
        {
            $this->_setProperties($properties);
        }

        public function getProperties()
        {
            return $this->_getProperties();
        }

        public function getOldProperties()
        {
            return $this->_getOldProperties();
        }

        public function getOldProperty($name)
        {
            return $this->_getOldProperty($name);
        }

        public function isRequiredProperty($name)
        {
            return $this->_isRequiredProperty($name);
        }

        public function getRequiredPropertiesList()
        {
            return $this->_required_properties;
        }

        public function isValid()
        {
            return $this->_isValid();
        }

        protected function _setRequiredProperty($name, $allowed_values = array())
        {
            $this->_required_properties[$name] = array(
                'required' => TRUE,
                'allowed_values' => $allowed_values
            );
        }

        protected function _unsetRequiredProperty($name)
        {
            $this->_required_properties[$name] = array(
                'required' => FALSE
            );
        }

        protected function _isRequiredProperty($name)
        {
            return !empty($this->_required_properties[$name]['required']) ? TRUE : FALSE;
        }

        protected function _verifyTypeIsString($value)
        {
            if (!is_string($value)) {
                throw new NetLicensingException('Expected string type, got ' . gettype($value));
            }
        }

        protected function _isValid()
        {
            $is_valid = TRUE;
            if ($this->_required_properties) {
                foreach ($this->_required_properties as $name => $data) {
                    if (!empty($data['required'])) {
                        if (empty($this->_properties[$name])) $is_valid = FALSE;
                        if (!empty($data['allowed_values']) && !in_array($this->_properties[$name], $data['allowed_values'])) $is_valid = FALSE;
                    }
                }
            }

            return $is_valid;
        }

        protected function _getProperty($name)
        {
            return isset($this->_properties[$name]) ? $this->_properties[$name] : '';
        }

        protected function _setProperty($name, $value)
        {
            if (is_bool($value)) $value = ($value) ? 'true' : 'false';

            $this->_verifyTypeIsString($value);

            $this->_properties[$name] = $value;
        }

        protected function _getOldProperty($name)
        {
            return isset($this->_old_properties[$name]) ? $this->_old_properties[$name] : '';
        }

        protected function _setProperties(array $properties)
        {
            if ($properties) {
                foreach ($properties as $name => $value) {
                    $this->_setProperty($name, $value);
                }
            }
        }

        protected function _getProperties()
        {
            return $this->_properties;
        }

        protected function _getOldProperties()
        {
            return $this->_old_properties;
        }
    }
}
