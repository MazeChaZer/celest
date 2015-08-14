<?php
/*
 * Copyright (c) 2012, 2013, Oracle and/or its affiliates. All rights reserved.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.  Oracle designates this
 * particular file as subject to the "Classpath" exception as provided
 * by Oracle in the LICENSE file that accompanied this code.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 *
 * You should have received a copy of the GNU General Public License version
 * 2 along with this work; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * Please contact Oracle, 500 Oracle Parkway, Redwood Shores, CA 94065 USA
 * or visit www.oracle.com if you need additional information or have any
 * questions.
 */

/*
 * This file is available under and governed by the GNU General Public
 * License version 2 only, as published by the Free Software Foundation.
 * However, the following notice accompanied the original version of this
 * file:
 *
 * Copyright (c) 2012, Stephen Colebourne & Michael Nascimento Santos
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 *  * Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 *  * Neither the name of JSR-310 nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace Php\Time\Chrono;

use Php\Time\DateTimeException;
use Php\Time\Temporal\TemporalField;
use Php\Time\Temporal\Temporal;
use Php\Time\Temporal\TemporalQuery;

/**
 * An era in the ISO calendar system.
 * <p>
 * The ISO-8601 standard does not define eras.
 * A definition has therefore been created with two eras - 'Current era' (CE) for
 * years on or after 0001-01-01 (ISO), and 'Before current era' (BCE) for years before that.
 *
 * <table summary="ISO years and eras" cellpadding="2" cellspacing="3" border="0" >
 * <thead>
 * <tr class="tableSubHeadingColor">
 * <th class="colFirst" align="left">year-of-era</th>
 * <th class="colFirst" align="left">era</th>
 * <th class="colLast" align="left">proleptic-year</th>
 * </tr>
 * </thead>
 * <tbody>
 * <tr class="rowColor">
 * <td>2</td><td>CE</td><td>2</td>
 * </tr>
 * <tr class="altColor">
 * <td>1</td><td>CE</td><td>1</td>
 * </tr>
 * <tr class="rowColor">
 * <td>1</td><td>BCE</td><td>0</td>
 * </tr>
 * <tr class="altColor">
 * <td>2</td><td>BCE</td><td>-1</td>
 * </tr>
 * </tbody>
 * </table>
 * <p>
 * <b>Do not use {@code ordinal()} to obtain the numeric representation of {@code IsoEra}.
 * Use {@code getValue()} instead.</b>
 *
 * @implSpec
 * This is an immutable and thread-safe enum.
 *
 * @since 1.8
 */
class IsoEra implements Era
{
    public static function init()
    {
        self::$BCE = new IsoEra(0);
        self::$CE = new IsoEra(1);
    }

    /**
     * The singleton instance for the era before the current one, 'Before Current Era',
     * which has the numeric value 0.
     * @return IsoEra
     */
    public static function BCE()
    {
        return self::$BCE;
    }

    /** @var IsoEra */
    private static $BCE;

    /**
     * The singleton instance for the current era, 'Current Era',
     * which has the numeric value 1.
     */
    public static function CE()
    {
        return self::$CE;
    }

    /** @var IsoEra */
    private static $CE;

    /** @var int */
    private $val;

    /**
     * @param $val int
     */
    private function __construct($val)
    {
        $this->val = $val;
    }

    //-----------------------------------------------------------------------
    /**
     * Obtains an instance of {@code IsoEra} from an {@code int} value.
     * <p>
     * {@code IsoEra} is an enum representing the ISO eras of BCE/CE.
     * This factory allows the enum to be obtained from the {@code int} value.
     *
     * @param $isoEra int the BCE/CE value to represent, from 0 (BCE) to 1 (CE)
     * @return IsoEra the era singleton, not null
     * @throws DateTimeException if the value is invalid
     */
    public static function of($isoEra)
    {
        switch ($isoEra) {
            case 0:
                return self::$BCE;
            case 1:
                return self::$CE;
            default:
                throw new DateTimeException("Invalid era: " . $isoEra);
        }
    }

//-----------------------------------------------------------------------
    /**
     * Gets the numeric era {@code int} value.
     * <p>
     * The era BCE has the value 0, while the era CE has the value 1.
     *
     * @return int the era value, from 0 (BCE) to 1 (CE)
     */
    public function getValue()
    {
        return $this->val;
    }

    /**
     * @inheritdoc
     */
    function isSupported(TemporalField $field)
    {
        EraDefaults::isSupported($this, $field);
    }

    /**
     * @inheritdoc
     */
    function range(TemporalField $field)
    {
        EraDefaults::range($this, $field);
    }

    /**
     * @inheritdoc
     */
    function get(TemporalField $field)
    {
        EraDefaults::get($this, $field);
    }

    /**
     * @inheritdoc
     */
    function getLong(TemporalField $field)
    {
        EraDefaults::getLong($this, $field);
    }

    /**
     * @inheritdoc
     */
    function query(TemporalQuery $query)
    {
        EraDefaults::query($this, $query);
    }

    /**
     * @inheritdoc
     */
    function adjustInto(Temporal $temporal)
    {
        EraDefaults::adjustInto($this, $temporal);
    }

    /**
     * @inheritdoc
     */
    function getDisplayName(TextStyle $style, Locale $locale)
    {
        EraDefaults::getDisplayName($this, $style, $locale);
    }

    public function __toString()
    {
        return $this->val === 0 ? 'BCE' : 'CE';
    }
}