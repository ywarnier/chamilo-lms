{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
    {{ form }}

    {% for resource in pagination %}
        {{ display.collapse(
            resource.iid,
            '#' ~ resource.courseCode ~'-'~  resource.iid ~ ' - ' ~ resource.title ~ ' - ' ~ resource.path,
            resource.resourceData,
            false,
            false
            )
        }}
    {% endfor %}

    {% if resource_count > pagination_length %}
        {{ pagination }}
    {% endif %}
{% endblock %}
