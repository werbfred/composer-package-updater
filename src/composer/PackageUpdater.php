<?php

/*********************************************************************************************************************/
/* Namespaces                                                                                                        */
/*********************************************************************************************************************/
namespace ComposerPackageUpdater\composer;
use       Composer\Composer;
use       Composer\DependencyResolver\Operation\InstallOperation;
use       Composer\DependencyResolver\Operation\UpdateOperation;
use       Composer\DependencyResolver\Operation\UninstallOperation;
use       Composer\EventDispatcher\EventDispatcher;
use       Composer\IO\IOInterface;
use       Composer\Installer\PackageEvent;

/*********************************************************************************************************************/
/* Class definition                                                                                                  */
/*********************************************************************************************************************/
class PackageUpdater
{
  /***********************************************************************************************************/
  /* Constants                                                                                               */
  /***********************************************************************************************************/
  public const DEPENCENCIES_SCRIPTS       = 'dependency-scripts';
  public const DEPENCENCIES_SCRIPTS_RUN   = 'run';
  public const DEPENCENCIES_SCRIPTS_TRUST = 'trust';

  /***********************************************************************************************************/
  /* Process package event                                                                                   */
  /***********************************************************************************************************/
  public static function processPackageEvent ( PackageEvent $event )
  {
    /*************************************************************************************************/
    /* Locals                                                                                        */
    /*************************************************************************************************/
    $io                 = $event->getIO ();
    $composer           = $event->getComposer ();
    $new_composer       = clone $composer;
    $root_package       = $new_composer->getPackage ();
    $root_package_extra = $root_package->getExtra ();
    $event_package      = null;

    /*************************************************************************************************/
    /* We want to execute only the scripts we will tell the event dispatcher to handle               */
    /*************************************************************************************************/
    $root_package->setScripts ( [] );

    /*************************************************************************************************/
    /* Do not handle if no acceptable configuration is being provided                                */
    /*************************************************************************************************/
    if ( (empty($root_package_extra[self::DEPENCENCIES_SCRIPTS])                                       == true)
      || (isset($root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_RUN])       == false)
      || (is_bool($root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_RUN])     == false)
      || ($root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_RUN]             !== true)
      || (empty($root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_TRUST])     == true)
      || (is_array($root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_TRUST])  == false) )
    {
      return;
    }

    /*************************************************************************************************/
    /* Let's get the operation being performed                                                       */
    /*************************************************************************************************/
    $event_operation = $event->getOperation ();

    /*************************************************************************************************/
    /* Update operations first... these are more likely to occur than the 2 other events :)          */
    /*************************************************************************************************/
    if ( is_a($event_operation,UpdateOperation::class) == true )
    {
      $event_package = $event_operation->getTargetPackage ();
    }
    elseif ( is_a($event_operation,InstallOperation::class) == true )
    {
      $event_package = $event_operation->getPackage ();
    }
    elseif ( is_a($event_operation,UninstallOperation::class) == true )
    {
      $event_package = $event_operation->getPackage ();
    }

    /*************************************************************************************************/
    /* Check that current package is amongst trusted packages                                        */
    /*************************************************************************************************/
    if ( (is_null($event_package)                                                                                               == false)
      && (in_array($event_package->getName(),$root_package_extra[self::DEPENCENCIES_SCRIPTS][self::DEPENCENCIES_SCRIPTS_TRUST]) == true) )
    {
      /*******************************************************************************************/
      /* Get package scripts                                                                     */
      /*******************************************************************************************/
      $package_scripts = $event_package->getScripts ();

      /*******************************************************************************************/
      /* Check if current event has scripts to be executed                                       */
      /*******************************************************************************************/
      if ( array_key_exists($event->getName(),$package_scripts) == true )
      {
        /*************************************************************************************/
        /* Build event dispatcher                                                            */
        /*************************************************************************************/
        $event_dispatcher = new EventDispatcher ( $new_composer, $io );

        /*************************************************************************************/
        /* Get list of event handlers                                                        */
        /*************************************************************************************/
        $event_handlers = $package_scripts[$event->getName()];

        /*************************************************************************************/
        /* Register handlers with current event                                              */
        /*************************************************************************************/
        if ( is_string($event_handlers) == true )
        {
          $event_dispatcher->addListener ( $event->getName(), $event_handlers );
        }
        /*************************************************************************************/
        /* Array with first item Callable and second the priority                            */
        /*************************************************************************************/
        elseif ( is_array($event_handlers) == true )
        {
          /*******************************************************************************/
          /* Add every single event handler                                              */
          /*******************************************************************************/
          foreach ( $event_handlers as $event_handler )
          {
            /*************************************************************************/
            /* Check handler validity                                                */
            /*************************************************************************/
            if ( is_string($event_handler) == true )
            {
              $event_dispatcher->addListener ( $event->getName(), $event_handler );
            }
          } // foreach ( $event_handlers as $event_handler )
        } // if ( is_string($event_handlers) == true )

        /*************************************************************************************/
        /* Heavily inspired from Composer\Script\EventDispatcher::doDispatch                 */
        /*************************************************************************************/
        try
        {
          /*******************************************************************************/
          /* Dispatch event                                                              */
          /*******************************************************************************/
          $event_dispatcher->dispatch ( $event->getName(), $event );
        }
        catch ( \Throwable $throwable )
        {
          /*******************************************************************************/
          /* Render exception message                                                    */
          /*******************************************************************************/
          $exception_message = strtr ( 'Exception : [:message]'
                                     , [ ':message' => $throwable->getMessage()
                                       , ] );

          $io->write ( $exception_message );

          /*******************************************************************************/
          /* Get stack trace from throwable                                              */
          /*******************************************************************************/
          $trace = explode ( '#', $throwable->getTraceAsString() );

          /*******************************************************************************/
          /* Render stack trace                                                          */
          /*******************************************************************************/
          foreach ( $trace as $idx => $entry )
          {
            /*************************************************************************/
            /* Stack trace starts with a # character thus avoid adding first entry   */
            /*************************************************************************/
            if ( $idx > 0 )
            {
              $io->write ( '#' . $entry );
            }
          } // foreach ( $trace as $idx => $entry )          
        }            
      } // if ( array_key_exists($event->getName(),$package_scripts) == true )
    } // if ( (is_null($event_package) == false) && ...
  } // end of method [ PackageUpdater::processPackageEvent ]

} // end of class [ PackageUpdater ]