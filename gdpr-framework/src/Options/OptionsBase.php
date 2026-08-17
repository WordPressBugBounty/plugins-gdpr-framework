<?php

namespace Codelight\GDPR\Options;

/**
 * Parent class for options.
 * Adapted from https://carlalexander.ca/designing-classes-wordpress-options-api/
 *
 * Class OptionsBase
 *
 * @package Codelight\GDPR\Options
 */
abstract class OptionsBase
{
    /* @var string */
    protected $prefix = 'gdpr_';

    /**
     * Auto-prefix all options
     *
     * @param $name
     * @return string
     */
    public function prefix($name)
    {   
        // Check for accidental duplicate prefix.
        // Fix: this compared strpos() against "" instead of 0. strpos() returns
        // int 0 when $name starts with the prefix and false when it does not --
        // it never returns "" -- so under strict comparison the guard was
        // unreachable and already-prefixed names were silently read from and
        // written to 'gdpr_gdpr_*'.
        if (0 === strpos($name, $this->prefix)) {
            trigger_error("You appear to have a duplicate prefix for option {$name}", E_USER_NOTICE);
            return $name;
        }

        return $this->prefix . $name;
    }

    /**
     * Checks if the option with the given name exists or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return null !== $this->get($name);
    }

    /**
     * Gets the option for the given name. Returns the default value if the value does not exist.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    abstract public function get($name, $default = null);

    /**
     * Removes the option with the given name.
     *
     * @param string $name
     */
    abstract public function delete($name);

    /**
     * Sets an option. Overwrites the existing option if the name is already in use.
     *
     * @param string $name
     * @param mixed  $value
     */
    abstract public function set($name, $value);
}