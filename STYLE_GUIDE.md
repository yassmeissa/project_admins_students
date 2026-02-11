# Guide de Cohérence Visuelle - Admin & Student Portal

## Couleurs et Thème

### Variables CSS
- **Primary Color**: #667eea (Bleu-Violet)
- **Secondary Color**: #764ba2 (Violet)
- **Success Color**: #27ae60 (Vert)
- **Danger Color**: #e74c3c (Rouge)
- **Dark Text**: #2c3e50 (Gris Foncé)
- **Light Bg**: #f8f9fa (Gris Clair)

### Dark Theme
- **Background**: Gradient #1a1a2e → #16213e
- **Card Background**: #34495e
- **Text**: #ecf0f1 (Gris Clair)
- **Secondary Text**: #bdc3c7

### Light Theme
- **Background**: Gradient #667eea → #764ba2
- **Page Background**: #f8f9fa
- **Card Background**: #ffffff
- **Text**: #2c3e50

## Frameworks et Librairies

### Bootstrap 5.3.0
- Tous les nouveaux templates utilisent Bootstrap 5.3.0
- CDN: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`

### Font Awesome 6.4.0
- Pour les icônes
- CDN: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`

## Structure HTML Standard

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #27ae60;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --border-color: #e0e0e0;
        }

        body {
            {% if theme == 'dark' %}
            background: #1a1a2e;
            color: #ecf0f1;
            {% else %}
            background: var(--light-bg);
            color: var(--dark-text);
            {% endif %}
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Dark theme styles */
        {% if theme == 'dark' %}
        .card {
            background: #34495e;
            color: #ecf0f1;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        {% endif %}
    </style>
</head>
<body>
    <!-- Content -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

## Composants Standards

### Navbar
```html
<div class="navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-icon-name"></i> Title</h1>
        <ul class="nav-links">
            <li><a href="#"><i class="fas fa-icon"></i> Link</a></li>
        </ul>
    </div>
</div>
```

### Toolbar
```html
<div class="toolbar">
    <!-- Theme Toggle -->
    <form action="{{ path('change_theme_route') }}" method="post" style="margin: 0;">
        <input type="hidden" name="theme" value="{{ theme == 'light' ? 'dark' : 'light' }}">
        <button type="submit" class="btn-theme">
            <i class="fas fa-{{ theme == 'light' ? 'moon' : 'sun' }}"></i>
            {{ theme == 'light' ? 'Mode Sombre' : 'Mode Clair' }}
        </button>
    </form>
</div>
```

### Cards
```html
<div class="card">
    <h3>Title</h3>
    <p>Content</p>
</div>
```

### Grid Layout
```html
<div class="grid-layout">
    <!-- Items -->
</div>
```

## Pages Mises à Jour

✅ `/student/` - Accueil étudiant (student/index.html.twig)
✅ `/student/{idS}/courses` - Mes cours (student_course/index.html.twig)
✅ `/student/{idS}/cours-disponibles` - Cours disponibles (student_course/disponibles.html.twig)
✅ `/student/{idS}/post_forum` - Forums (post_forum/index.html.twig)
✅ `/student/{idS}/courses/{courseId}/lessons` - Leçons (student_course/lessons.html.twig)
✅ `/student/{idS}/qcm-recommendations` - Recommandations QCM (student_qcm/recommendations.html.twig)

## Pages à Mettre à Jour

- [ ] Admin Dashboard (admin/index.html.twig)
- [ ] Course Management (course/index.html.twig, course/show.html.twig)
- [ ] QCM Management (qcm/index.html.twig, qcm/show.html.twig)
- [ ] Student QCM (student_qcm/qcm.html.twig)
- [ ] Post Forum (post_forum/new.html.twig, post_forum/edit.html.twig, post_forum/show.html.twig)

## Notes de Développement

1. Toujours utiliser les variables CSS pour les couleurs
2. Supporter le thème dark/light avec conditionals Twig `{% if theme == 'dark' %}`
3. Utiliser Bootstrap Grid pour la responsivité
4. Font: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
5. Éspacement standard: 20px entre les sections
6. Border radius: 8-12px pour les cards
7. Transition: all 0.3s ease
8. Box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) pour light, 0 4px 12px rgba(0, 0, 0, 0.3) pour dark
