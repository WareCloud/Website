<?php
/**
 * @brief		Templates Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Content
 * @since		25 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * templates
 */
class _templates extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'template_manage' );
		parent::execute();
	}
	
	/**
	 * Import the IN_DEV templates
	 * 
	 * @return void
	 */
	public function importInDev()
	{
		\IPS\cms\Theme::importInDev('database');
		\IPS\cms\Theme::importInDev('page');

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates' ), 'completed' );
	}

	/**
	 * Import dialog
	 *
	 * @return void
	 */
	public function import()
	{
		$form = new \IPS\Helpers\Form( 'form', 'next' );

		$form->add( new \IPS\Helpers\Form\Upload( 'cms_templates_import', NULL, FALSE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'cms_templates_import' ) );

		if ( $values = $form->values() )
		{
			if ( $values['cms_templates_import'] )
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['cms_templates_import'], $tempFile );

				/* Initate a redirector */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates&do=importProcess&file=' . urlencode( $tempFile ) . '&key=' . md5_file( $tempFile ) ) );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ) );
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'cms_templates_import_title', $form, FALSE );
	}

	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function importProcess()
	{
		if ( !file_exists( \IPS\Request::i()->file ) or md5_file( \IPS\Request::i()->file ) !== \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'generic_error', '3T259/3', 403, '' );
		}

		/* Open XML file */
		$xml = new \XMLReader;
		$xml->open( \IPS\Request::i()->file );
		$prefix = date('m') . '.' . date('d') . '.' . date('y') . '_';

		if ( ! @$xml->read() )
		{
			\IPS\Output::i()->error( 'xml_upload_invalid', '2C163/1', 403, '' );
		}
		$xml->read();
		while ( $xml->read() )
		{
			if ( $xml->name === 'template' )
			{
				$templates = \IPS\cms\Theme::i()->getRawTemplates( 'cms', NULL, NULL, \IPS\cms\Theme::RETURN_ALL );
				$node      = new \SimpleXMLElement( $xml->readOuterXML() );
				$attrs     = array();

				foreach( $node->attributes() as $k => $v )
				{
					$attrs[ $k ] = (string) $v;
				}

				/* Any kids of your own? */
				foreach( $node->children() as $k => $v )
				{
					$tryJson = json_decode( $v, TRUE );
					$attrs[ $k ] =  ( $tryJson ) ? $tryJson : (string) $v;
				}

				if ( isset( $attrs['template_title'] ) and isset( $attrs['template_location'] ) and isset( $attrs['template_group'] ) )
				{
					/* Got this template? */
					if ( $attrs['template_location'] === 'database' )
					{
						/* Database templates are really governed by the group as they are a collection of templates */
						if ( isset( $templates['cms'][$attrs['template_location']][$attrs['template_group']] ) )
						{
							$attrs['template_group'] = $prefix . $attrs['template_group'];
						}
					}
					else
					{
						/* But post and block templates are not */
						if ( isset( $templates['cms'][$attrs['template_location']][$attrs['template_group']] ) )
						{
							$exists = FALSE;
							foreach ( $templates['cms'][$attrs['template_location']][$attrs['template_group']] as $key => $template )
							{
								if ( $template['template_title'] === $attrs['template_title'] )
								{
									$exists = TRUE;
									break;
								}
							}

							if ( $exists )
							{
								$attrs['template_title'] = $prefix . $attrs['template_title'];
							}
						}
					}

					$obj                 = new \IPS\cms\Templates;
					$obj->location       = $attrs['template_location'];
					$obj->group          = $attrs['template_group'];
					$obj->title          = $attrs['template_title'];
					$obj->params         = $attrs['template_params'];
					$obj->content        = $attrs['template_content'];
					$obj->original_group = $attrs['template_original_group'];
					$obj->type           = 'template';
					$obj->user_created   = 1;
					$obj->user_edited    = 1;
					$obj->desc           = '';
					$obj->save();

					$obj->key = $obj->location . \IPS\Http\Url::seoTitle( $obj->group ) . '_' . \IPS\Http\Url::seoTitle( $obj->title ) . '_' . $obj->id;
					$obj->save();
				}
			}

			$xml->next();
		}

		/* Done */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates&do=manage' ), 'cms_templates_imported' );
	}

	/**
	 * Export templates
	 *
	 * @return void
	 */
	public function export()
	{
		$form = new \IPS\Helpers\Form( 'form', 'next' );
		$templates = array();

		$form->addMessage( 'cms_templates_export_description', 'ipsMessage ipsMessage_information' );
		foreach( \IPS\cms\Templates::getTemplates( \IPS\cms\Templates::RETURN_DATABASE_ONLY + \IPS\cms\Templates::RETURN_ALL ) as $template )
		{
			$title = \IPS\cms\Templates::readableGroupName( $template->group );

			if ( $template->location === 'database' )
			{
				$templates['database'][ $template->group ] = $title;
			}
			else if ( $template->location === 'block' )
			{
				$templates['block'][ $template->key ] = $title . ' &gt; ' . $template->title;
			}
			else if ( $template->location === 'page' )
			{
				$templates['page'][ $template->key ] = $title . ' &gt; ' . $template->title;
			}
		}

		foreach( $templates as $location => $data )
		{
			$form->add( new \IPS\Helpers\Form\CheckboxSet( 'templates_' . $location, FALSE, FALSE, array( 'options' => $data ) ) );
		}

		if ( $values = $form->values() )
		{
			$exportTemplates = array();
			foreach( $templates as $location => $data )
			{
				if ( isset( $values[ 'templates_' . $location ] ) and count( $values[ 'templates_' . $location ] ) )
				{
					if ( $location === 'database' )
					{
						$tmp = \IPS\cms\Theme::i()->getRawTemplates( 'cms', array('database'), array_values( $values[ 'templates_' . $location ] ) );

						foreach( $tmp['cms'] as $loc => $data )
						{
							if ( $loc === $location )
							{
								foreach ( $data as $key => $tdata )
								{
									foreach ( $tdata as $tkey => $tkdata )
									{
										$exportTemplates['cms'][ $location ][ $key ][ $tkey ] = $tkdata;
									}
								}
							}
						}
					}
					else
					{
						$tmp = \IPS\cms\Theme::i()->getRawTemplates( 'cms', array( $location ), NULL, \IPS\cms\Theme::RETURN_DATABASE_ONLY + \IPS\cms\Theme::RETURN_ALL );

						foreach( $tmp['cms'] as $loc => $data )
						{
							if ( $loc === $location )
							{
								foreach( $data as $key => $tdata )
								{
									foreach( $tdata as $tkey => $tkdata )
									{
										if ( in_array( $tkey, $values[ 'templates_' . $location ] ) )
										{
											$exportTemplates['cms'][ $location ][ $key ][ $tkey ] = $tkdata;
										}
									}
								}
							}
						}
					}
				}
			}

			if ( count( $exportTemplates ) )
			{
				/* Init */
				$xml = new \XMLWriter;
				$xml->openMemory();
				$xml->setIndent( TRUE );
				$xml->startDocument( '1.0', 'UTF-8' );

				/* Root tag */
				$xml->startElement('templates');

				foreach ( $exportTemplates as $app => $location )
				{
					foreach ( $location as $key => $data )
					{
						foreach ( $data as $group => $template )
						{
							foreach( $template as $key => $templateData )
							{
								/* Initiate the <template> tag */
								$xml->startElement( 'template' );

								foreach ( $templateData as $k => $v )
								{
									if ( ! in_array( \substr( $k, 9 ), array( 'content', 'params' ) ) )
									{
										$xml->startAttribute( $k );
										$xml->text( $v );
										$xml->endAttribute();
									}
								}

								/* Write (potential) HTML fields */
								foreach( array( 'template_params', 'template_content' ) as $field )
								{
									if ( isset( $templateData[ $field ] ) )
									{
										$xml->startElement( $field );
										if ( preg_match( '/<|>|&/', $templateData[ $field ] ) )
										{
											$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $templateData[ $field ] ) );
										}
										else
										{
											$xml->text( $templateData[ $field ] );
										}
										$xml->endElement();
									}
								}

								/* Close the <template> tag */
								$xml->endElement();
							}
						}
					}
				}

				/* Finish */
				$xml->endDocument();

				\IPS\Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', "pages_templates.xml" ) ) );
			}
			else
			{
				\IPS\Output::i()->error( 'cms_no_templates_selected', '1T285/1', 403, '' );
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'cms_templates_export_title', $form, FALSE );
	}

	/**
	 * List templates
	 * 
	 * @return void
	 */
	public function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'template_add_edit' );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__cms_pages_templates');

		if ( \IPS\Theme::designersModeEnabled() )
		{
			$link = \IPS\Http\Url::internal( 'app=core&module=customization&controller=themes&do=designersmode' );
			\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack('cms_theme_designer_mode_warning', NULL, array( 'sprintf' => array( $link ) ) ), 'information', NULL, FALSE );
		}
		else
		{
			$request = array(
				't_location' => ( isset( \IPS\Request::i()->t_location ) ) ? \IPS\Request::i()->t_location : NULL,
				't_group'    => ( isset( \IPS\Request::i()->t_group ) ) ? \IPS\Request::i()->t_group : NULL,
				't_key'      => ( isset( \IPS\Request::i()->t_key ) ) ? \IPS\Request::i()->t_key : NULL,
				't_type'     => ( isset( \IPS\Request::i()->t_type ) ) ? \IPS\Request::i()->t_type : 'templates',
			);

			switch ( $request['t_type'] )
			{
				default:
				case 'template':
					$flag = \IPS\cms\Templates::RETURN_ONLY_TEMPLATE;
					break;
				case 'js':
					$flag = \IPS\cms\Templates::RETURN_ONLY_JS;
					break;
				case 'css':
					$flag = \IPS\cms\Templates::RETURN_ONLY_CSS;
					break;
			}

			$templates = \IPS\cms\Templates::buildTree( \IPS\cms\Templates::getTemplates( $flag + \IPS\cms\Templates::RETURN_DATABASE_ONLY ) );

			$current = NULL;

			if ( !empty( $request['t_key'] ) )
			{
				try
				{
					$current = \IPS\cms\Templates::load( $request['t_key'] );
				}
				catch ( \OutOfRangeException $ex )
				{

				}
			}

			/* Load first block */
			if ( $current === NULL )
			{
				foreach ( $templates as $type => $_templates )
				{
					if ( $_templates )
					{
						$test = key( $_templates );

						try
						{
							$current = \IPS\cms\Templates::load( $test );
						}
						catch ( \OutofRangeException $e )
						{
							foreach ( $_templates as $location => $group )
							{
								foreach ( $group as $name => $template )
								{
									$current = $template;
									break 3;
								}
							}
						}
					}
				}
			}

			/* Display */
			\IPS\Output::i()->responsive = FALSE;

			/* A button */
			if ( \IPS\IN_DEV )
			{
				\IPS\Output::i()->sidebar['actions']['add'] = array(
					'icon'  => 'cog',
					'title' => 'content_import_dev_templates',
					'link'  => \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=importInDev" ),
					'data'  => array()
				);
			}

			\IPS\Output::i()->sidebar['actions']['download'] = array(
				'icon'  => 'download',
				'title' => 'cms_templates_export_title',
				'link'  => \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=export" ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('cms_templates_export_title') )
			);

			\IPS\Output::i()->sidebar['actions']['upload'] = array(
				'icon'  => 'upload',
				'title' => 'cms_templates_import_title',
				'link'  => \IPS\Http\Url::internal( "app=cms&module=pages&controller=templates&do=import" ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('cms_templates_import_title') )
			);

			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'templates/templates.css', 'cms', 'admin' ) );
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_templates.js', 'cms' ) );

			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'templates' )->templates( $templates, $current, $request );
		}
	}
	
	/**
	 * Add Container
	 *
	 * @return	void
	 */
	public function addContainer()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'template_add' );
	
		$type = \IPS\Request::i()->type;
	
		/* Build form */
		$form = new \IPS\Helpers\Form();
	
		$form->add( new \IPS\Helpers\Form\Text( 'container_name', NULL, TRUE ) );
		$form->hiddenValues['type'] = $type;
	
		if ( $values = $form->values() )
		{
			$type = \IPS\Request::i()->type;
				
			$newContainer = \IPS\cms\Templates\Container::add( array(
					'name' => $values['container_name'],
					'type' => 'template_' . $type
			) );
	
			if( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array(
					'id'   => $newContainer->id,
					'name' => $newContainer->title,
				) );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates' ), 'saved' );
			}
		}
	
		/* Display */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'content_template_add_container_' . $type, $form, FALSE );
	}
	
	/**
	 * Add Template
	 * This is never used for editing as this is done via the template manager
	 *
	 * @return	void
	 */
	public function addTemplate()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'template_add_edit' );
		
		$type = \IPS\Request::i()->type;
		
		/* Build form */
		$form = new \IPS\Helpers\Form();
		$form->hiddenValues['type'] = $type;
		
		$form->add( new \IPS\Helpers\Form\Text( 'template_title', NULL, TRUE, array( 'regex' => '/^([A-Z_][A-Z0-9_]+?)$/i' ) ) );

		/* Very specific */
		if ( $type === 'database' )
		{
			$groups = array(); /* I was sorely tempted to put 'Radiohead', 'Beatles' in there */
			foreach( \IPS\cms\Databases::$templateGroups as $key => $group )
			{
				$groups[ $key ] = \IPS\Member::loggedIn()->language()->addToStack( 'cms_new_db_template_group_' . $key );
			}

			$form->add( new \IPS\Helpers\Form\Select( 'database_template_type', NULL, FALSE, array(
				'options' => $groups
			) ) );

			$databases = array( 0 => \IPS\Member::loggedIn()->language()->addToStack('cms_new_db_assign_to_db_none' ) );
			foreach( \IPS\cms\Databases::databases() as $obj )
			{
				$databases[ $obj->id ] = $obj->_title;
			}

			$form->add( new \IPS\Helpers\Form\Select( 'database_assign_to', NULL, FALSE, array(
				'options' => $databases
			) ) );
		}
		else if ( $type === 'block' )
		{
			$plugins = array();
			foreach ( \IPS\Db::i()->select( "*", 'core_widgets', array( 'embeddable=1') ) as $widget )
			{
				try
				{
					$plugins[ \IPS\Application::load( $widget['app'] )->_title ][ $widget['app'] . '__' . $widget['key'] ] = \IPS\Member::loggedIn()->language()->addToStack( 'block_' . $widget['key'] );
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			$form->add( new \IPS\Helpers\Form\Select( 'block_template_plugin_import', NULL, FALSE, array(
					'options' => $plugins
			) ) );
		
			$form->add( new \IPS\Helpers\Form\Node( 'block_template_theme_import', NULL, TRUE, array(
				'class' => '\IPS\Theme'
			) ) );
		}
		else
		{
			/* Page, css, js */
			switch( $type )
			{
				default:
					$flag = \IPS\cms\Theme::RETURN_ONLY_TEMPLATE;
				break;
				case 'page':
					$flag = \IPS\cms\Theme::RETURN_PAGE;
				break;
				case 'js':
					$flag = \IPS\cms\Theme::RETURN_ONLY_JS;
				break;
				case 'css':
					$flag = \IPS\cms\Theme::RETURN_ONLY_CSS;
				break;
			}

			$templates = \IPS\cms\Theme::i()->getRawTemplates( array(), array(), array(), $flag | \IPS\cms\Theme::RETURN_ALL_NO_CONTENT );

			$groups = array();

			if ( isset( $templates['cms'][ $type ] ) )
			{
				foreach( $templates['cms'][ $type ] as $group => $data )
				{
					$groups[ $group ] = \IPS\cms\Templates::readableGroupName( $group );
				}
			}

			if ( ! count( $groups ) )
			{
				$groups[ $type ] = \IPS\cms\Templates::readableGroupName( $type );
			}

			$form->add( new \IPS\Helpers\Form\Radio( 'theme_template_group_type', 'existing', FALSE, array(
				            'options'  => array( 'existing' => 'theme_template_group_o_existing',
				                                 'new'	    => 'theme_template_group_o_new' ),
				            'toggles'  => array( 'existing' => array( 'group_existing' ),
				                                 'new'      => array( 'group_new' ) )
			            ) ) );

			$form->add( new \IPS\Helpers\Form\Text( 'template_group_new', NULL, FALSE, array( 'regex' => '/^([a-z_][a-z0-9_]+?)?$/' ), NULL, NULL, NULL, 'group_new' ) );
			$form->add( new \IPS\Helpers\Form\Select( 'template_group_existing', NULL, FALSE, array( 'options' => $groups ), NULL, NULL, NULL, 'group_existing' ) );
		}

		if ( ! \IPS\Request::i()->isAjax() AND $type !== 'database' )
		{
			$form->add( new \IPS\Helpers\Form\TextArea( 'template_content', NULL ) );
		}
	
		if ( $values = $form->values() )
		{
			$type = \IPS\Request::i()->type;

			if ( $type == 'database' )
			{
				/* We need to copy templates */
				$group     = \IPS\cms\Databases::$templateGroups[ $values['database_template_type' ] ];
				$templates = iterator_to_array( \IPS\Db::i()->select( '*', 'cms_templates', array( 'template_location=? AND template_group=? AND template_user_edited=0 AND template_user_created=0', 'database', $group ) ) );

				foreach( $templates as $template )
				{
					unset( $template['template_id'] );
					$template['template_original_group'] = $template['template_group'];
					$template['template_group'] = str_replace( '-', '_', \IPS\Http\Url::seoTitle( $values['template_title'] ) );

					$save = array();
					foreach( $template as $k => $v )
					{
						$k = \mb_substr( $k, 9 );
						$save[ $k ] = $v;
					}

					/* Make sure template tags call the correct group */
					if ( mb_stristr( $save['content'], '{template' ) )
					{
						preg_match_all( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', $save['content'], $matches, PREG_SET_ORDER );

						/* Work out the plugin and the values to pass */
						foreach( $matches as $index => $array )
						{
							preg_match_all( '/(.+?)=' . $array[ 2 ] . '(.+?)' . $array[ 2 ] . '\s?/', $array[ 1 ], $submatches );

							$plugin = array_shift( $submatches[ 1 ] );
							if ( $plugin == 'template' )
							{
								$value   = array_shift( $submatches[ 2 ] );
								$options = array();

								foreach ( $submatches[ 1 ] as $k => $v )
								{
									$options[ $v ] = $submatches[ 2 ][ $k ];
								}

								if ( isset( $options['app'] ) and $options['app'] == 'cms' and isset( $options['location'] ) and $options['location'] == 'database' and isset( $options['group'] ) and $options['group'] == $template['template_original_group'] )
								{
									$options['group'] = $template['template_group'];

									$replace = '{template="' . $value . '" app="' . $options['app'] . '" location="' . $options['location'] . '" group="' . $options['group'] . '" params="' . ( isset($options['params']) ? $options['params'] : NULL ) . '"}';

									$save['content'] = str_replace( $matches[$index][0], $replace, $save['content'] );
								}
							}
						}
					}

					$newTemplate = \IPS\cms\Templates::add( $save );
				}

				if ( $values['database_assign_to'] )
				{
					try
					{
						$db   = \IPS\cms\Databases::load( $values['database_assign_to'] );
						$key  = 'template_' . $values['database_template_type'];
						$db->$key = $template['template_group'];
						$db->save();
					}
					catch( \OutOfRangeException $ex ) { }
				}
			}
			else if ( $type === 'block' )
			{
				$save = array(
					'title'	   => str_replace( '-', '_', \IPS\Http\Url::seoTitle( $values['template_title'] ) ),
					'params'   => isset( $values['template_params'] ) ? $values['template_params'] : null,
					'location' => $type
				);

				/* Get template */
				list( $widgetApp, $widgetKey ) = explode( '__', $values['block_template_plugin_import'] );

				/* Find it from the normal template system */
				$plugin = \IPS\Widget::load( \IPS\Application::load( $widgetApp ), $widgetKey, uniqid(), array() );

				$location = $plugin->getTemplateLocation();

				$theme = ( \IPS\IN_DEV ) ? \IPS\Theme::master() : $values['block_template_theme_import'];
				$templateBits  = $theme->getRawTemplates( $location['app'], $location['location'], $location['group'], \IPS\Theme::RETURN_ALL );
				$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];

				$save['content'] = $templateBit['template_content'];
				$save['params']  = $templateBit['template_data'];
				$save['group']   = $widgetKey;
				$newTemplate = \IPS\cms\Templates::add( $save );
			}
			else
			{
				$save = array( 'title' => $values['template_title'] );

				/* Page, css, js */
				if ( $type == 'js' or $type == 'css' )
				{
					$fileExt = ( $type == 'js' ) ? '.js' : ( $type == 'css' ? '.css' : NULL );
					if ( $fileExt AND ! preg_match( '#' . preg_quote( $fileExt, '#' ) . '$#', $values['template_title'] ) )
					{
						$values['template_title'] .= $fileExt;
					}

					$save['title'] = $values['template_title'];
					$save['type']  = $type;
				}
				
				if ( $type === 'page' AND $values['theme_template_group_type'] == 'existing' AND $values['template_group_existing'] == 'custom_wrappers' )
				{
					$save['params'] = '$html=NULL, $title=NULL';
				}
				
				if ( $type === 'page' AND $values['theme_template_group_type'] == 'existing' AND $values['template_group_existing'] == 'page_builder' )
				{
					$save['params'] = '$page, $widgets';
				}

				$save['group'] = ( $values['theme_template_group_type'] == 'existing' ) ? $values['template_group_existing'] : $values['template_group_new'];

				if ( isset( $values['template_content'] ) )
				{
					$save['content'] = $values['template_content'];
				}

				$save['location'] = $type;

				$newTemplate = \IPS\cms\Templates::add( $save );
			}

			/* Done */
			if( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array(
					'id'		=> $newTemplate->id,
					'title'		=> $newTemplate->title,
					'params'	=> $newTemplate->params,
					'desc'		=> $newTemplate->description,
					'container'	=> $newTemplate->container,
					'location'	=> $newTemplate->location
				)	);
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates' ), 'saved' );
			}
		}
	
		/* Display */
		$title = \strip_tags( \IPS\Member::loggedIn()->language()->get( 'content_template_add_template_' . $type ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( $title, $form, FALSE );
		\IPS\Output::i()->title  = $title;
	}
	
	/**
	 * Delete a template
	 * This can be either a CSS template or a HTML template
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'template_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		$key    = \IPS\Request::i()->t_key;
		$return = array(
			'template_content' => NULL,
			'template_id' 	   => NULL
		);
		
		try
		{
			\IPS\cms\Templates::load( $key )->delete();
			
			/* Now reload */
			try
			{
				$template = \IPS\cms\Templates::load( $key );
				
				$return['template_location'] = $template->location;
				$return['template_content']  = $template->content;
				$return['template_id']		 = $template->id;
				$return['InheritedValue']    = ( $template->user_added ) ? 'custom' : ( $template->user_edited ? 'changed' : 0 );
			}
			catch( \OutOfRangeException $ex )
			{
				
			}
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'node_error', '3S121/1', 500, '' );
		}

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( $return );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates' ), 'completed' );
		}
	}
	
	/**
	 * Saves a template
	 * 
	 * @return void
	 */
	public function save()
	{
		$key = \IPS\Request::i()->t_key;
		
		$contentKey = 'editor_' . $key;

		$content     = \IPS\Request::i()->$contentKey;
		$description = \IPS\Request::i()->t_description;
		$variables   = isset( \IPS\Request::i()->t_variables ) ? \IPS\Request::i()->t_variables : '';
		
		try
		{
			$obj = \IPS\cms\Templates::load( $key );
			
			$obj->location    = \IPS\Request::i()->t_location;
			$obj->group       = empty( \IPS\Request::i()->t_group ) ? null : \IPS\Request::i()->t_group;
			$obj->title       = \IPS\Request::i()->t_name;
			$obj->params	  = $variables;
			$obj->content     = $content;
			if( $description )
			{
				$obj->description = $description;
			}
			$obj->save();
			
			$url = array(
				't_location'  => $obj->location,
				't_group'     => $obj->group,
				't_key'       => $key
			);
		}
		catch( \Exception $ex )
		{
			\IPS\Output::i()->json( array( 'msg' => $ex->getMessage() ) );
		}
		
		/* reload to return new item Id */
		$obj = \IPS\cms\Templates::load( $key );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();
		
		/* Clear block caches */
		\IPS\cms\Blocks\Block::deleteCompiled();
		
		if(  \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( array( 'template_id' => $obj->id, 'template_title' => $obj->title, 'template_container' => $obj->container, 'template_user_added' => $obj->user_created ) );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=cms&module=pages&controller=templates&' . implode( '&', $url ) ), 'completed' );
		}
	}
}