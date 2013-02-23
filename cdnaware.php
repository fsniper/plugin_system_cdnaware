<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Example system plugin
 */
class plgSystemCDNAware extends JPlugin
{
  /**
   * Constructor
   *
   * For php4 compatability we must not use the __constructor as a constructor for plugins
   * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
   * This causes problems with cross-referencing necessary for the observer design pattern.
   *
   * @access      protected
   * @param       object  $subject The object to observe
   * @param       array   $config  An array that holds the plugin configuration
   * @since       1.0
   */
  function plgSystemCache( &$subject, $config )
  {
    parent::__construct( $subject, $config );

    // Do some extra initialisation in this constructor if required
  }

  /**
   * Do something onAfterRender 
   */
  function onAfterRender()
  {
    $plugin =& JPluginHelper::getPlugin('system','cdnaware');
    $params = new JParameter( $plugin->params );
    $domains = explode(",", $params->get('domains'));
    $extensions = explode(",", $params->get('extensions'));

    $extensionlist = '';
    foreach ($extensions as $ext) {
      if ($ext[0] != '.') $ext = '.' . $ext;
      $extensionlist .= $ext . '|';
    }
    $extensionlist = substr($extensionlist, 0, -1);

    function urlChanger($str, $ext, $relative = false) {
      $plugin =& JPluginHelper::getPlugin('system','cdnaware');
      $params = new JParameter( $plugin->params );
      
      if ($str[0] != '/' && $relative) {
        $str = JURI::base(true) . '/' . $str;
      }
      
      $cdns = explode(",", $params->get('cdn'));
      return  sprintf('src="%s%s%s"',  $cdns[rand(0, count($cdns)-1)] , $str , $ext);
    }

    $app =& JFactory::getApplication();
    $buffer = JResponse::getBody();

    $base   = JURI::base(true);
    
    //Change absolute url images 
    // This should be done before relative urls
    // The ' is not allowed in html attribute declarations!!
    // But some use them.. 

    $regex     = '#src\s*=\s*("|\')http://(' . implode('|', $domains) . ')([^:"\']*)(' . $extensionlist .')("|\')#em';
    $buffer    = preg_replace($regex, "urlChanger('$3','$4')", $buffer);

    //Change relative url images
    $regex     = '#src\s*=\s*("|\')([^:"]*)(' . $extensionlist . ')("|\')#em';
    $buffer    = preg_replace($regex, "urlChanger('$2','$3', true)", $buffer);
    

    JResponse::setBody($buffer);
    return true;

  }
}

