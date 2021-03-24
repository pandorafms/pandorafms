<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module macros</h1>
<p>
    Any number of custom module macros may be defined. The recommended format for macro names is:
</p>
<pre>
    _macroname_
</pre>

<p>
    For example:
</p>
<ol>
    <li class="lato_bolder lato_bolderfont_12pt">
        _technology_
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        _modulepriority_
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        _contactperson_
    </li>
</ol>

<p>
    This macros can be used in module alerts.
</p>

<h2>IF THE MODULE IS A WEB MODULE ANALYSIS TYPE: </h2>

<p>
Dynamic macros will have a special format starting with @ and will have these possible substitutions:
</p>
<ol>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT (current date/time with user-defined format)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_nh (hours)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_nm (minutes)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_nd (days)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_ns (seconds)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_nM (month)
    </li>
    <li class="lato_bolder lato_bolderfont_12pt">
        @DATE_FORMAT_nY (years)
    </li>
</ol>
<p>
Where "n" can be a number without a sign (positive) or negative.
</p>
<p>
And FORMAT follows the standard of perl's strftime:
    http://search.cpan.org/~dexter/POSIX-strftime-GNU-0.02/lib/POSIX/strftime/GNU.pm
</p>
<p>
    Examples:
</p>
<pre>
    @DATE_%Y-%m-%d %H:%M:%S
    @DATE_%H:%M:%S_300s
    @DATE_%H:%M:%S_-1h
</pre>