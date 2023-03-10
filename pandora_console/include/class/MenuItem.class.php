<?php

/**
 * Menu Item class.
 */
class MenuItem
{


    /**
     * Constructor
     *
     * @param string  $text            Caption for show.
     * @param string  $url             Raw URL.
     * @param string  $sec             URL sec.
     * @param string  $sec2            URL sec2.
     * @param string  $parameters      Added parameters to builded url.
     * @param string  $id              Unique identificator.
     * @param string  $icon            Icon.
     * @param string  $class           Class of his own.
     * @param array   $submenu         Submenus.
     * @param integer $refr            Refr.
     * @param array   $acl             ACLs affected.
     * @param array   $activationToken Config Token for active this item.
     * @param boolean $display         True if must be displayed.
     * @param boolean $enabled         True if must be Enabled.
     *
     * @return MenuItem.
     */
    public function __construct(
        private string $text='',
        private string $url='',
        private string $sec='',
        private string $sec2='',
        private string $parameters='',
        private string $id='',
        private string $icon='',
        private string $class='',
        private array $submenu=[],
        private int $refr=0,
        private array $acl=[],
        private array $activationToken=[],
        private bool $display=true,
        private bool $enabled=true,
    ) {
        if (empty($this->id) === true && empty($this->text) === false) {
            $this->id = str_replace(' ', '_', $text);
        }

        return $this;
    }


    /**
     * Set Text. The caption of the option.
     *
     * @param string $text Text.
     *
     * @return void.
     */
    public function setText(string $text)
    {
        $this->text = $text;
    }


    /**
     * Get Text
     *
     * @return string.
     */
    public function getText()
    {
        return $this->text;
    }


    /**
     * Set URL. Raw URL avoid sec and sec2 information.
     *
     * @param string $url Url.
     *
     * @return void.
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }


    /**
     * Get URL
     *
     * @return string.
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * Set sec
     *
     * @param string $sec Sec.
     *
     * @return void.
     */
    public function setSec(string $sec)
    {
        $this->sec = $sec;
    }


    /**
     * Get sec
     *
     * @return string.
     */
    public function getSec()
    {
        return $this->sec;
    }


    /**
     * Set sec2
     *
     * @param string $sec2 Sec2.
     *
     * @return void.
     */
    public function setSec2(string $sec2)
    {
        $this->sec2 = $sec2;
    }


    /**
     * Get sec2
     *
     * @return string.
     */
    public function getSec2()
    {
        return $this->sec2;
    }


    /**
     * Set parameters. Added parameters for builded url (sec + sec2).
     *
     * @param string $parameters Parameters.
     *
     * @return void.
     */
    public function setParameters(string $parameters)
    {
        $this->parameters = $parameters;
    }


    /**
     * Get parameters
     *
     * @return string.
     */
    public function getParameters()
    {
        return $this->parameters;
    }


    /**
     * Set id. This is useful for identify the option selected.
     *
     * @param string $id Id.
     *
     * @return void.
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }


    /**
     * Get id
     *
     * @return string.
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set icon. Must be relative path.
     *
     * @param string $icon Icon.
     *
     * @return void.
     */
    public function setIcon(string $icon)
    {
        $this->icon = $icon;
    }


    /**
     * Get icon
     *
     * @return string.
     */
    public function getIcon()
    {
        return $this->icon;
    }


    /**
     * Set class.
     *
     * @param string $class Class.
     *
     * @return void.
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }


    /**
     * Get class
     *
     * @return string.
     */
    public function getClass()
    {
        return $this->class;
    }


    /**
     * Set submenu. Array with options under this selection.
     *
     * @param array $submenu Submenu.
     *
     * @return void.
     */
    public function setSubmenu(array $submenu)
    {
        $this->submenu = $submenu;
    }


    /**
     * Get Submenu
     *
     * @return array.
     */
    public function getSubmenu()
    {
        return $this->submenu;
    }


    /**
     * Set ACLs. Attach only allowed ACLs in an array.
     *
     * @param array $acl ACL.
     *
     * @return void.
     */
    public function setACL(array $acl)
    {
        $this->acl = $acl;
    }


    /**
     * Get ACLs
     *
     * @return array.
     */
    public function getACL()
    {
        return $this->acl;
    }


    /**
     * Set activation token.
     *
     * @param array $activationToken ACL.
     *
     * @return void.
     */
    public function setActivationToken(array $activationToken)
    {
        $this->activationToken = $activationToken;
    }


    /**
     * Get activation token.
     *
     * @return array.
     */
    public function getActivationToken()
    {
        return $this->activationToken;
    }


    /**
     * Set refr
     *
     * @param integer $refr Refr.
     *
     * @return void.
     */
    public function setRefr(int $refr)
    {
        $this->refr = $refr;
    }


    /**
     * Get Refr
     *
     * @return integer.
     */
    public function getRefr()
    {
        return $this->refr;
    }


    /**
     * Set display. The item will be exists if this value is true.
     *
     * @param boolean $display Display.
     *
     * @return void.
     */
    public function setDisplay(bool $display)
    {
        $this->display = $display;
    }


    /**
     * Get Display
     *
     * @return boolean.
     */
    public function getDisplay()
    {
        return $this->display;
    }


    /**
     * Set enabled
     *
     * @param boolean $enabled Enabled.
     *
     * @return void.
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }


    /**
     * Get enabled
     *
     * @return boolean.
     */
    public function getEnabled()
    {
        return $this->enabled;
    }


    /**
     * Returns true if this object have submenu.
     *
     * @return boolean.
     */
    public function hasSubmenu()
    {
        return empty($this->getSubmenu()) === false;
    }


    /**
     * Returns true if this object can be displayed.
     *
     * @return boolean.
     */
    public function canBeDisplayed()
    {
        // Global config.
        global $config;
        $response = true;

        if ($this->getDisplay() === false) {
            // Display value is false.
            $response = false;
        } else if (empty($this->getACL()) === false) {
            // Check all the ACLs.
            $acls = $this->getACL();
            foreach ($acls as $acl) {
                $response = check_acl($config['id_user'], 0, $acl);
                // In false case, end the check.
                if ($response === false) {
                    break;
                }
            }
        }

        return $response;
    }


}
