import React from "react";
import Svg, { Circle, Ellipse, Path, Polygon, Rect } from "react-native-svg";

type Props = {
  width?: number | string;
  height?: number | string;
};

/** Hand-drawn farm scene (sky, hills, barn, goat/pig/cow silhouettes) — no photo assets in the project. */
export default function FarmIllustration({ width = "100%", height = "100%" }: Props) {
  return (
    <Svg width={width} height={height} viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice">
      <Rect width={800} height={600} fill="#E9F9EF" />
      <Circle cx={660} cy={120} r={70} fill="#DCFCE7" opacity={0.7} />
      <Path d="M0,320 Q120,260 260,300 T520,290 T800,320 L800,600 L0,600 Z" fill="#DCFCE7" opacity={0.8} />
      <Path d="M0,400 Q160,340 340,380 T700,370 L800,400 L800,600 L0,600 Z" fill="#E9F9EF" />
      <Path d="M0,460 Q200,410 400,440 T800,430 L800,600 L0,600 Z" fill="#15803D" opacity={0.9} />

      <Rect x={560} y={392} width={90} height={62} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Polygon points="552,392 605,354 658,392" fill="#15803D" />
      <Rect x={596} y={422} width={18} height={32} fill="#15803D" />

      <Ellipse cx={130} cy={472} rx={46} ry={26} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Circle cx={84} cy={458} r={16} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Path d="M74,442 l-6,-14 M90,440 l4,-14" fill="none" stroke="#15803D" strokeWidth={3} strokeLinecap="round" />
      <Path d="M106,494 v18 M130,496 v18 M152,494 v18" stroke="#15803D" strokeWidth={4} fill="none" strokeLinecap="round" />

      <Ellipse cx={270} cy={502} rx={40} ry={24} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Circle cx={230} cy={494} r={15} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Ellipse cx={219} cy={496} rx={6} ry={5} fill="#DCFCE7" />
      <Path d="M250,522 v14 M274,524 v14" stroke="#15803D" strokeWidth={4} fill="none" strokeLinecap="round" />

      <Ellipse cx={430} cy={482} rx={50} ry={28} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Circle cx={382} cy={470} r={17} fill="#FFFFFF" stroke="#15803D" strokeWidth={3} />
      <Path d="M370,452 l-8,-10 M394,450 l8,-10" fill="none" stroke="#15803D" strokeWidth={3} strokeLinecap="round" />
      <Ellipse cx={440} cy={478} rx={10} ry={8} fill="#15803D" opacity={0.5} />
      <Path d="M404,506 v16 M430,508 v16 M456,506 v16" stroke="#15803D" strokeWidth={4} fill="none" strokeLinecap="round" />

      <Rect x={0} y={560} width={800} height={40} fill="#15803D" opacity={0.95} />
    </Svg>
  );
}
