#! /usr/bin/env python
# -*- coding: utf-8 -*-

#
# This file is part of PLACEMENT software
# PLACEMENT helps users to bind their processes to one or more cpu-cores
#
# PLACEMENT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your options) any later version.
#
#  Copyright (C) 2015-2018 Emmanuel Courcelle
#  PLACEMENT is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with PLACEMENT.  If not, see <http://www.gnu.org/licenses/>.
#
#  Authors:
#        Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
#

# ObjectPage base classe, used to implement the ObjectPage pattern
# See https://www.selenium.dev/documentation/test_practices/encouraged/page_object_models/
# All ObjectPage classes shoud extend this class

import os

class ObjectPage(object):

    def __init__(self,driver):
        self.__driver = driver
        self.__baseUrl = os.environ['BASE_URL']

    def _getDriver(self):
        return self.__driver

    def _getBaseUrl(self):
        return self.__baseUrl
