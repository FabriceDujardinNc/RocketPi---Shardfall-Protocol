// GroundBlend.shader — Sol qui dégrade radialement du BÉTON (centre, zone industrielle)
// vers du NATUREL (bord, vers les montagnes), selon la distance XZ au centre.
// Surface shader Built-in RP (le projet ne tourne pas en URP).
Shader "RocketPi/GroundBlend"
{
    Properties
    {
        _ConcreteColor ("Béton (centre)", Color)   = (0.30, 0.31, 0.34, 1)
        _NaturalColor  ("Naturel (bord)", Color)   = (0.34, 0.42, 0.22, 1)
        _EdgeColor     ("Terre (transition)", Color)= (0.45, 0.38, 0.26, 1)
        _InnerRadius   ("Rayon béton", Float)       = 56
        _OuterRadius   ("Rayon naturel", Float)     = 74
        _Glossiness    ("Smoothness", Range(0,1))   = 0.05
    }
    SubShader
    {
        Tags { "RenderType"="Opaque" }
        LOD 200

        CGPROGRAM
        #pragma surface surf Standard fullforwardshadows
        #pragma target 3.0

        struct Input { float3 worldPos; };

        fixed4 _ConcreteColor, _NaturalColor, _EdgeColor;
        float  _InnerRadius, _OuterRadius, _Glossiness;

        void surf (Input IN, inout SurfaceOutputStandard o)
        {
            float d   = length(IN.worldPos.xz);
            float mid  = (_InnerRadius + _OuterRadius) * 0.5;
            // béton → terre (transition) → naturel
            float t1 = smoothstep(_InnerRadius, mid, d);
            float t2 = smoothstep(mid, _OuterRadius, d);
            fixed3 col = lerp(_ConcreteColor.rgb, _EdgeColor.rgb, t1);
            col        = lerp(col, _NaturalColor.rgb, t2);
            o.Albedo    = col;
            o.Smoothness= _Glossiness;
            o.Metallic  = 0;
        }
        ENDCG
    }
    FallBack "Diffuse"
}
