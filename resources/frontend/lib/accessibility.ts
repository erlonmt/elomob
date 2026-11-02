/**
 * Kinds of disability a profile may declare, in the order the API lists them. "stepLabel" names what a step offers
 * when it meets the need.
 */
export const accessibilityNeeds = [
  {
    code: 'wheelchair',
    title: 'Cadeira de rodas',
    description: 'Paradas e trechos sem degraus, veículos adaptados e integrações acessíveis',
    stepLabel: 'Cadeira de rodas',
    icon: 'accessible',
  },
  {
    code: 'reduced_mobility',
    title: 'Mobilidade reduzida',
    description: 'Trajetos sem escadas e integrações acessíveis',
    stepLabel: 'Sem escadas',
    icon: 'stairs',
  },
  {
    code: 'visual',
    title: 'Deficiência visual',
    description: 'Piso tátil nas paradas e aviso sonoro no trajeto',
    stepLabel: 'Piso tátil e aviso sonoro',
    icon: 'need-visual',
  },
  {
    code: 'hearing',
    title: 'Deficiência auditiva',
    description: 'Painéis nas paradas e letreiro no veículo',
    stepLabel: 'Informação visual',
    icon: 'need-hearing',
  },
] as const;

/** Titles of the given needs, in catalog order, skipping unknown codes. */
export const needTitles = (codes: readonly string[]) =>
  accessibilityNeeds.filter(need => codes.includes(need.code)).map(need => need.title);
