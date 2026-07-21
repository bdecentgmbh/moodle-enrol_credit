// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle App handler for credit enrolment (CoreEnrolDelegate, action "self").
 *
 * @copyright 2026 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const getEnrolmentInfoCacheKey = (id) => {
    return 'EnrolCredit:' + id;
};

const getEnrolmentInfo = (id) => {
    const site = this.CoreSitesProvider.getCurrentSite();

    return site.read('enrol_credit_get_instance_info', {
        instanceid: id,
    }, {
        cacheKey: getEnrolmentInfoCacheKey(id),
    });
};

const creditEnrol = (courseId) => {
    const site = this.CoreSitesProvider.getCurrentSite();

    return site.write('enrol_credit_enrol_user', {
        courseid: courseId,
    }).then((response) => {
        if (response.status) {
            return true;
        }
        if (response.warnings && response.warnings.length) {
            throw new this.CoreWSError(response.warnings[0]);
        }
        throw Error('WS enrol_credit_enrol_user failed without warnings');
    });
};

var result = {
    getInfoIcons: (courseId) => {
        return this.CoreEnrolService.getSupportedCourseEnrolmentMethods(courseId, 'credit').then((enrolments) => {
            if (!enrolments.length) {
                return [];
            }
            return [{
                label: 'plugin.enrol_credit.pluginname',
                icon: 'fas-coins',
            }];
        });
    },

    enrol: (method) => {
        return getEnrolmentInfo(method.id).then((info) => {
            if (info.status !== true) {
                // The user cannot enrol; show the reason returned by the server.
                const message = typeof info.status === 'string'
                    ? info.status.replace(/<[^>]*>/g, ' ').trim()
                    : this.TranslateService.instant('plugin.enrol_credit.canntenrol');

                this.CoreDomUtilsProvider.showAlert(
                    this.TranslateService.instant('plugin.enrol_credit.pluginname'),
                    message
                );

                return false;
            }

            const checkout = this.TranslateService.instant('plugin.enrol_credit.checkout', {
                $a: {
                    credit_cost: info.cost,
                    user_credits: info.usercredits,
                },
            });

            return this.CoreDomUtilsProvider.showConfirm(
                checkout,
                this.TranslateService.instant('plugin.enrol_credit.pluginname'),
                this.TranslateService.instant('plugin.enrol_credit.purchase')
            ).then(() => {
                return this.CoreDomUtilsProvider.showModalLoading('core.loading', true).then((modal) => {
                    return creditEnrol(method.courseid).catch((error) => {
                        this.CoreDomUtilsProvider.showErrorModalDefault(error, 'plugin.enrol_credit.enrolfailed', true);

                        return false;
                    }).finally(() => {
                        modal.dismiss();
                    });
                });
            }).catch(() => {
                // User cancelled the confirmation dialog.
                return false;
            });
        });
    },

    invalidate: (method) => {
        const site = this.CoreSitesProvider.getCurrentSite();

        return site.invalidateWsCacheForKey(getEnrolmentInfoCacheKey(method.id));
    },
};

result;
