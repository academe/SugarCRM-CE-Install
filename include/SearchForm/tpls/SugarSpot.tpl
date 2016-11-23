{*
/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2012 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/

*}

{literal}
<style type="text/css">
.QuickView {
height:12px;
cursor:pointer;
}
.SpanQuickView {
visibility:hidden;
padding-right:5px;
}
.gs_link {
border:0;
}
</style>
{/literal}
<div id="SpotResults">
{if !empty($displayResults)}
{foreach from=$displayResults key=module item=data}
<div>
    {if isset($appListStrings.moduleList[$module])}
        {$appListStrings.moduleList[$module]}
    {else}
        {$module}
    {/if}
    {if !empty($displayMoreForModule[$module])}
    {assign var="more" value=$displayMoreForModule[$module]}
    <small class='more' onclick="DCMenu.spotZoom('{$more.query}', '{$module}', '{$more.offset}');">({$more.countRemaining} {$appStrings.LBL_SEARCH_MORE})</small>
    {/if}
</div>
<table class="gs_table">
    {if isset($data.link)}
        <tr>
            <td>{sugar_getimage name='blank.gif' width='16' height='16' border='0'}</td>
            <td>
                <a href="index.php?&module=Home&action=UnifiedSearch&query_string={$data.link.query_encoded}">{$data.link.total} {$appStrings.LBL_SEARCH_RESULTS}</a>
            </td>
        </tr>
        </table>
    {else}
        {foreach from=$data key=id item=name}
        <tr onmouseover="DCMenu.showQuickViewIcon('{$id}')" onmouseout="DCMenu.hideQuickViewIcon('{$id}')">
        <td>
        <span id="gs_div_{$id}" class="SpanQuickView">
        <img id="gs_img_{$id}" class="QuickView" src="themes/default/images/Search.gif" alt="quick_view_{$id}" onclick="DCMenu.showQuickView('{$module}', '{$id}');">
        </span>
        </td>
        <td><a href="index.php?module={$module}&action=DetailView&record={$id}" class="gs_link">{$name}</a></td>
        </tr>
        {/foreach}
        </table>
    {/if}
{/foreach}
{else}
{$appStrings.LBL_EMAIL_SEARCH_NO_RESULTS}
{/if}
<p>
<button onclick="document.location.href='index.php?module=Home&action=UnifiedSearch&search_form=false&advanced=false&query_string={$queryEncoded}'">{$appStrings.LBL_EMAIL_SHOW_READ}</button>
</div>