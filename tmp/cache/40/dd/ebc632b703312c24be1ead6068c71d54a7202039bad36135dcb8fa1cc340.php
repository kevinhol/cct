<?php

/* Home.twig */
class __TwigTemplate_40ddebc632b703312c24be1ead6068c71d54a7202039bad36135dcb8fa1cc340 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "
<p>
";
        // line 3
        if (isset($context["viewData"])) { $_viewData_ = $context["viewData"]; } else { $_viewData_ = null; }
        echo twig_escape_filter($this->env, $this->getAttribute($_viewData_, "name"), "html", null, true);
        echo "
</p>
";
    }

    public function getTemplateName()
    {
        return "Home.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  23 => 3,  19 => 1,);
    }
}
