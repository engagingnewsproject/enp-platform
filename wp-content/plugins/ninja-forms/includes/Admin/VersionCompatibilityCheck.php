<?php

namespace NinjaForms\Includes\Admin;

if (!defined('ABSPATH')) exit;

class VersionCompatibilityCheck
{
    /**
   * Array construct requirements for compatibility check
   *
   * @var array
   */
  const COMPATIBILITY_CHECK_REQUIREMENTS = [
    'className' => 'string',
    'minVersion' => 'string',
    'title' => 'string',
    'message' => 'string',
    'int' => 'integer',
    'link'=>'string'
  ];

  /** @var array */
  protected $compatiblityCheckCollection;



  /**
   * Notices to add
   * 
   * Uses NF notices array structure
   *
   * @var array
   */
  protected $compatibilityNotices = [];

  /**
   * Activate checks and notices for plugin's envirnoment compatibility 
   *
   * @return void
   */
  public function activate(): void
  {
    add_action('ninja_forms_loaded', array($this, 'ensureVersionCompatibility'), 0);
  }

  /**
   * If Authorize.net and NF core versions re incompatible, display user notice
   *
   * NOTE: this does not stop functioning of plugin, but warns user that
   * functionality may be affected
   *
   * @return void
   */
  public function ensureVersionCompatibility(): void
  {
    $this->loadCompatibilityConfiguration();

    $this->constructCompatiblityNotices();

    add_filter('nf_admin_notices', [$this, 'addIncompatibleVersionsNotice']);
  }

  /**
   * Load compatibility check configuration 
   *
   * @return void
   */
  protected function loadCompatibilityConfiguration(): void
  {
    $this->compatiblityCheckCollection = \Ninja_Forms()->config('VersionCompatibilityCheck');

  }

  /**
   * Iterate collection, check compatibility, append notices
   *
   * @return void
   */
  protected function constructCompatiblityNotices(): void
  {
    foreach ($this->compatiblityCheckCollection as $compatiblityCheck) {
      if (!$this->isValidConstruct($compatiblityCheck)) {
       
        continue;
      }

      $versionCompatiblity = $this->checkVersionCompatibility(
        $compatiblityCheck['className'],
        $compatiblityCheck['minVersion']
      );

      if ($versionCompatiblity) {
        
        continue;
      }

      $this->appendNotice(
        $compatiblityCheck['className'],
        $compatiblityCheck['title'],
        $compatiblityCheck['message'],
        $compatiblityCheck['int'],
        $compatiblityCheck['link']
      );
    }
  }

  /**
   * Ensure compatibility check construct is valid
   *
   * @param array $compatiblityCheck
   * @return boolean false on invalid construct
   */
  protected function isValidConstruct(array $compatiblityCheck): bool
  {
    $return = true;

    foreach (self::COMPATIBILITY_CHECK_REQUIREMENTS as $requiredKey => $requiredType) {

      if (
        !isset($compatiblityCheck[$requiredKey])
        || $requiredType !== \gettype($compatiblityCheck[$requiredKey])
      ) {
        $return = false;
      }

    }
    return $return;
  }

  /**
   * Check that required versions are installed for proper functionality
   *
   * Default is to pass checks; only fail if known version incompatibility
   * 
   * @return boolean Compatible TRUE, incompatible FALSE
   */
  protected function checkVersionCompatibility(string $className, string $requiredVersion): bool
  {
    $return = true;

    $classVersion = $this->getClassVersion($className);

    if ('' != $classVersion && \version_compare($classVersion, $requiredVersion, '<')) {

      $return = false;
    }
    
    return $return;
  }

  /**
   * Append a notice to the internal collection of notices
   *
   * @param string $className
   * @param string $title
   * @param string $message
   * @param integer $int
   * @return void
   */
  protected function appendNotice(string $className, string $title, string $message, int $int, string $link): void
  {
    $this->compatibilityNotices[$className . '_compatibility_notice'] = [
      'title' => $title,
      'msg' => $message,
      'int' => $int,
      'link'=>$link
    ];
  }

  /**
   * Determine the version of the request class name
   *
   * @param string $className Name of class to check 
   * @return string Version of the class, empty string if class doesn't exist or
   * does not have VERSION constant defined
   */
  protected function getClassVersion(string $className): string
  {
    $return = '';

    if (\class_exists($className) ){

      $reflectionClass = new \ReflectionClass($className);

      $return = $reflectionClass->getConstant('VERSION')?$reflectionClass->getConstant('VERSION'):''; 

    }
    
    return $return;
  }

  /**
   * Add NF admin notice for incompatible versions
   *
   * @param array $notices
   * @return array
   */
  public function addIncompatibleVersionsNotice($notices): array
  {
    foreach($this->compatibilityNotices as $noticeKey=>$newNotice){
      
        $notices[$noticeKey]=[
          'title'=>$newNotice['title'],
          'msg'=>$newNotice['msg'],
          'int'=>$newNotice['int'],
          'link'=>$newNotice['link']
        ];
    }    
    
    return $notices;
  }
}
