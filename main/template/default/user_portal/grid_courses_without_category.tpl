{% if not courses is empty %}
    <div class="grid-courses">
        <div class="row">
            {% for item in courses %}
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="items my-courses">
                        <div class="image">
                            {% if item.is_special_course %}
                                <div class="pin">{{ item.icon }}</div>
                            {% endif %}
                            {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                <img src="{{ item.image }}" class="img-responsive">
                            {% else %}
                                <a title="{{ item.title }}" href="{{ item.link }}">
                                    <img src="{{ item.image }}" alt="{{ item.title }}" class="img-responsive">
                                </a>
                            {% endif %}
                            {% if item.category != '' %}
                                <span class="category">{{ item.category }}</span>
                                <div class="cribbon"></div>
                            {% endif %}
                            {% if item.edit_actions != '' %}
                                <div class="admin-actions">
                                    {% if item.document == '' %}
                                        <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </a>
                                    {% else %}
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </a>
                                            {{ item.document }}
                                        </div>
                                    {% endif %}
                                </div>
                            {% endif %}
                        </div>
                        <div class="description">
                            <div class="block-title">
                                <h4 class="title" title="{{ item.title }}">
                                    {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                        {{ item.title_cut }}
                                        <span class="code-title">{{ item.code_course }}</span>{{ item.url_marker }}
                                    {% else %}
                                        <a title="{{ item.title }}" href="{{ item.link }}">{{ item.title_cut }}</a>
                                        <span class="code-title">{{ item.code_course }}</span>{{ item.url_marker }}
                                    {% endif %}
                                </h4>
                            </div>
                            <div class="block-author">
                                {% if item.teachers | length > 6 %}
                                    <a id="plist-{{ loop.index }}" data-trigger="focus" tabindex="0" role="button" class="btn btn-default panel_popover" data-toggle="popover" title="{{ 'CourseTeachers' | get_lang }}" data-html="true">
                                        <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                                    </a>
                                    <div id="popover-content-plist-{{ loop.index }}" class="hide">
                                        {% for teacher in item.teachers %}
                                        <div class="popover-teacher">
                                            <a href="{{ teacher.url }}" class="ajax"
                                               data-title="{{ teacher.firstname }} {{ teacher.lastname }}" >
                                                <img title="{{ teacher.firstname }} {{ teacher.lastname }}" src="{{ teacher.avatar }}"/>
                                            </a>
                                            <div class="teachers-details">
                                                <h5>
                                                    <a href="{{ teacher.url }}" class="ajax"
                                                       data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                                    </a>
                                                </h5>
                                            </div>
                                        </div>
                                        {% endfor %}
                                    </div>
                                {% else %}
                                    {% for teacher in item.teachers %}
                                        {% if item.teachers | length <= 2 %}
                                            <a href="{{ teacher.url }}" class="ajax"
                                               data-title="{{ teacher.firstname }} {{ teacher.lastname }}" title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                <img title="{{ teacher.firstname }} {{ teacher.lastname }}" src="{{ teacher.avatar }}"/>
                                            </a>
                                            <div class="teachers-details">
                                                <h5>
                                                    <a href="{{ teacher.url }}" class="ajax"
                                                       data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                        {{ teacher.firstname }} {{ teacher.lastname }}
                                                    </a>
                                                </h5>
                                                <p>{{ 'Teacher' | get_lang }}</p>
                                            </div>
                                        {% elseif item.teachers | length <= 6 %}
                                            <a href="{{ teacher.url }}" class="ajax"
                                               data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                <img title="{{ teacher.firstname }} {{ teacher.lastname }}" src="{{ teacher.avatar }}"/>
                                            </a>
                                        {% endif %}
                                    {% endfor %}
                                {% endif %}
                            </div>
                            {% if item.notifications %}
                                <div class="notifications">{{ item.notifications }}</div>
                            {% endif %}
                            {% if item.student_info %}
                                {% if item.student_info.progress is not null or item.student_info.score is not null or item.student_info.certificate is not null %}
                                    {% set one_column = item.student_info.score is null and item.student_info.certificate is null %}
                                    <div class="course-student-info">
                                        <div class="student-info">
                                            <div class="row">
                                                <div class="{{ one_column ? 'col-xs-12' : 'col-xs-8' }}">
                                                    {{ 'CourseProgress'|get_lang }}
                                                    <div class="progress">
                                                        <div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="{{ item.student_info.progress }}"
                                                                aria-valuemin="0" aria-valuemax="100" style="width: {{ item.student_info.progress }}%;">
                                                            {{ 'XPercent'|get_lang|format(item.student_info.progress) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="{{ one_column ? '' : 'col-xs-4' }}">
                                                    {% if item.student_info.score is not null %}
                                                        <div>{{ "StudentCourseScoreX" | get_lang | format(item.student_info.score) }}</div>
                                                    {% endif %}
                                                    {% if item.student_info.certificate is not null %}
                                                        <div title="{{ "StudentCourseCertificateX" | get_lang | format(item.student_info.certificate) }}">
                                                            <i class="fa fa-certificate text-warning" aria-hidden="true"></i>
                                                            {{ item.student_info.certificate }}
                                                        </div>
                                                    {% endif %}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {% endif %}
                            {% endif %}

                            {{ item.unregister_button }}
                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
{% endif %}