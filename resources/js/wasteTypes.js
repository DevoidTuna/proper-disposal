export const WASTE_TYPES = [
    { value: 'pilhas', label: 'Pilhas e baterias', icon: 'mdi-battery', color: 'red-darken-1' },
    { value: 'oleo', label: 'Óleo de cozinha', icon: 'mdi-oil', color: 'amber-darken-2' },
    { value: 'eletronicos', label: 'Eletrônicos', icon: 'mdi-laptop', color: 'blue-darken-1' },
    { value: 'lampadas', label: 'Lâmpadas', icon: 'mdi-lightbulb-on', color: 'deep-orange-darken-1' },
    { value: 'vidro', label: 'Vidro', icon: 'mdi-bottle-wine', color: 'teal-darken-1' },
    { value: 'plastico', label: 'Plástico', icon: 'mdi-bottle-soda-classic', color: 'light-blue-darken-1' },
    { value: 'metal', label: 'Metal', icon: 'mdi-silverware-fork-knife', color: 'blue-grey-darken-1' },
    { value: 'papel', label: 'Papel', icon: 'mdi-newspaper-variant', color: 'brown-darken-1' },
    { value: 'reciclaveis', label: 'Recicláveis (secos)', icon: 'mdi-recycle', color: 'green-darken-1' },
    { value: 'pneus', label: 'Pneus', icon: 'mdi-tire', color: 'grey-darken-2' },
    { value: 'tampinhas', label: 'Tampinhas', icon: 'mdi-bottle-soda', color: 'purple-lighten-1' },
    { value: 'esponjas', label: 'Esponjas', icon: 'mdi-spray-bottle', color: 'cyan-darken-1' },
    { value: 'entulho', label: 'Entulho / RCC', icon: 'mdi-dump-truck', color: 'orange-darken-2' },
    { value: 'volumosos', label: 'Volumosos', icon: 'mdi-sofa', color: 'deep-purple-lighten-1' },
    { value: 'poda', label: 'Resíduos de poda', icon: 'mdi-leaf', color: 'light-green-darken-1' },
    { value: 'medicamentos', label: 'Medicamentos', icon: 'mdi-pill', color: 'pink-darken-2' },
    { value: 'outros', label: 'Outros / logística reversa', icon: 'mdi-dots-horizontal-circle', color: 'blue-grey' },
];

export const wasteMeta = (value) => WASTE_TYPES.find((t) => t.value === value);
export const wasteLabel = (value) => wasteMeta(value)?.label ?? value;
