import Combobox from '../../Components/Combobox';

const inputCls =
    'w-full px-3 py-2 border-2 border-wheat rounded-lg text-xs sm:text-sm font-semibold bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[10px] font-bold text-earth uppercase mb-1';

export default function BeneficiaryForm({ row, index, options, onChange, onRemove, peopleSearch }) {
    const set = (field, value) => onChange(index, field, value);

    const relationshipOptions = (options.relationships || []).map((r) => ({ id: r.id, label: r.title }));
    const typeBenefitOptions = (options.type_benefits || []).map((t) => ({ id: t.id, label: t.title }));
    const stateOptions = (options.states || []).map((s) => ({ id: s.id, label: s.title }));
    const reasonOptions = (options.reason_disqualifications || []).map((r) => ({ id: r.id, label: r.title }));

    return (
        <div className="p-3 bg-gray-50 rounded-lg border border-wheat">
            <div className="flex items-center justify-between border-b border-wheat pb-2 mb-3">
                <span className="text-xs font-bold text-leaf uppercase">Beneficiario #{index + 1}</span>
                <button type="button" onClick={() => onRemove(index)} className="text-red-500 hover:text-red-700">
                    <i className="fas fa-times-circle" />
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <div>
                    <label className={labelCls}>Persona *</label>
                    <Combobox
                        value={row.person_id}
                        onChange={(v) => set('person_id', v)}
                        onSearch={peopleSearch}
                        selectedLabel={row.person_label}
                        placeholder="Buscar persona por nombre o DNI..."
                        minQuery={2}
                    />
                </div>
                <div>
                    <label className={labelCls}>Parentesco *</label>
                    <Combobox
                        value={row.relationship_id}
                        onChange={(v) => set('relationship_id', v)}
                        options={relationshipOptions}
                        placeholder="Seleccionar..."
                    />
                </div>
            </div>

            <p className="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">
                Datos Clínicos <span className="font-normal normal-case text-gray-400">(opcional)</span>
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                <div>
                    <label className={labelCls}>Peso (kg)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.weight ?? ''}
                        onChange={(e) => set('weight', e.target.value)}
                        className={inputCls}
                        placeholder="65.50"
                    />
                </div>
                <div>
                    <label className={labelCls}>Talla (cm)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.height ?? ''}
                        onChange={(e) => set('height', e.target.value)}
                        className={inputCls}
                        placeholder="160.00"
                    />
                </div>
                <div>
                    <label className={labelCls}>HMG (g/dL)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.hmg ?? ''}
                        onChange={(e) => set('hmg', e.target.value)}
                        className={inputCls}
                        placeholder="12.50"
                    />
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <div>
                    <label className={labelCls}>F. Inicio Beneficio</label>
                    <input
                        type="date"
                        value={row.date_begin ?? ''}
                        onChange={(e) => set('date_begin', e.target.value)}
                        className={inputCls}
                    />
                </div>
                <div>
                    <label className={labelCls}>F. Fin Beneficio</label>
                    <input
                        type="date"
                        value={row.date_end ?? ''}
                        onChange={(e) => set('date_end', e.target.value)}
                        className={inputCls}
                    />
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label className={labelCls}>Tipo de Beneficio</label>
                    <Combobox
                        value={row.type_benefit_id}
                        onChange={(v) => set('type_benefit_id', v)}
                        options={typeBenefitOptions}
                        placeholder="Seleccionar..."
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>Estado</label>
                    <Combobox
                        value={row.history_state_id}
                        onChange={(v) => set('history_state_id', v)}
                        options={stateOptions}
                        placeholder="Seleccionar..."
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>Motivo Descalificación</label>
                    <Combobox
                        value={row.reason_disqualification_id}
                        onChange={(v) => set('reason_disqualification_id', v)}
                        options={reasonOptions}
                        placeholder="Ninguno"
                        allowClear
                    />
                </div>
            </div>
        </div>
    );
}
