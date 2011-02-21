<?php

class dmWidgetContentImagegridView extends dmWidgetPluginView
{
  
  public function configure()
  {
    parent::configure();
    
    $this->addRequiredVar(array('medias', 'method', 'animation'));

    $this->addStylesheet(array('dmWidgetImagegridPlugin.view','lib.colorbox'));
    
    $this->addJavascript(array('lib.colorbox', 'dmWidgetImagegridPlugin.view'));
    
  }

  protected function filterViewVars(array $vars = array())
  {
    $vars = parent::filterViewVars($vars);
    
    // extract media ids
    $mediaIds = array();
    foreach($vars['medias'] as $index => $mediaConfig)
    {
      $mediaIds[] = $mediaConfig['id'];
    }
    
    // fetch media records
    $mediaRecords = empty($mediaIds) ? array() : $this->getMediaQuery($mediaIds)->fetchRecords()->getData();
    
    // sort records
    $this->mediaPositions = array_flip($mediaIds);
    usort($mediaRecords, array($this, 'sortRecordsCallback'));
    
    // build media tags
    $medias = array();
    foreach($mediaRecords as $index => $mediaRecord)
    {
      $mediaTag = $this->getHelper()->media($mediaRecord);
  
      if (!empty($vars['width']) || !empty($vars['height']))
      {
        $mediaTag->size(dmArray::get($vars, 'width'), dmArray::get($vars, 'height'));
      }
  
      $mediaTag->method($vars['method']);
  
      if ($vars['method'] === 'fit')
      {
        $mediaTag->background($vars['background']);
      }
      
      if ($alt = $vars['medias'][$index]['alt'])
      {
        $mediaTag->alt($this->__($alt));
      }
      
      if ($quality = dmArray::get($vars, 'quality'))
      {
        $mediaTag->quality($quality);
      }
      
      $medias[] = array(
        'id'    =>  $vars['medias'][$index]['id'],
        'alt'   =>  $vars['medias'][$index]['alt'],
        'tag'   => $mediaTag,
        'link'  => $vars['medias'][$index]['link']
      );
    }
  
    // replace media configuration by media tags
    $vars['medias'] = $medias;
    
    return $vars;
  }
  
  protected function sortRecordsCallback(DmMedia $a, DmMedia $b)
  {
    return $this->mediaPositions[$a->get('id')] > $this->mediaPositions[$b->get('id')];
  }
  
  protected function getMediaQuery($mediaIds)
  {
    return dmDb::query('DmMedia m')
    ->leftJoin('m.Folder f')
    ->whereIn('m.id', $mediaIds);
  }

  protected function doRender()
  {
    if ($this->isCachable() && $cache = $this->getCache())
    {
      return $cache;
    }
    
    $vars = $this->getViewVars();
    $helper = $this->getHelper();
    
//    $html = $helper->open('ul.dm_widget_content_imagegrid.list', array('json' => array(
//      'animation' => $vars['animation'],
//      'delay'     => dmArray::get($vars, 'delay', 3)
//    )));


    $columns = 4;
    $i=0;
//    $html = $helper->open('ul.dm_widget_content_imagegrid.list');
    $html = $helper->open('table cellpadding="0" cellspacing="0" width="100%"');

    foreach($vars['medias'] as $media)
    {
      $i++;
      if ( ($i-1) % $columns == 0) {$html .= $helper->open('tr');}
//      $html .= $helper->tag('li.element', $media['link']
//      ? $helper->link($media['link'])->text($media['tag'])
//      : $helper->link("media:".$media['id'])->text($media['tag'])._tag('div',$media['alt'])
//      );
      $html .= $helper->tag('td.imagegrid width="'.intval(100/$columns).'%" valign="top"',$media['link']
        ? $helper->link($media['link'])->text($media['tag'])
        :  $helper->link("media:".$media['id'])->text($media['tag'])._tag('div', $helper->link("media:".$media['id'])->text($media['alt']))
        );
      if ( ($i) % $columns == 0) {$html .= $helper->close('tr');}
    }
    
//    $html .= '</ul><div style="clear:both;"></div>';
    $html .= $helper->close('table');
    
    if ($this->isCachable())
    {
      $this->setCache($html);
    }
    
    return $html;
  }
  
  protected function doRenderForIndex()
  {
    $alts = array();
    foreach($this->compiledVars['medias'] as $media)
    {
      if (!empty($media['alt']))
      {
        $alts[] = $media['alt'];
      }
    }
    
    return implode(', ', $alts);
  }
  
}