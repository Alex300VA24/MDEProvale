import { useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Combobox from '../../Components/Combobox';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/socios-beneficiarios';
const inputCls =
    'w-full px-3.5 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1';
const Optional = () => <span className="text-earth font-normal normal-case tracking-normal">(opcional)</span>;

export async function submitInlinePersona(personState) {
    if (!personState.isNew) {
        if (!personState.personId) throw new Error('Debe seleccionar una persona.');
        return personState.personId;
    }

    const { personData, reniecPhotoToken } = personState;
    if (!personData.names || !personData.dni || !personData.father_lastname || !personData.mother_lastname) {
        throw new Error('Complete los datos obligatorios de la persona (DNI, nombres y apellidos).');
    }

    const payload = {
        names: personData.names,
        dni: personData.dni,
        father_lastname: personData.father_lastname,
        mother_lastname: personData.mother_lastname,
        birthdate: personData.birthdate || null,
        gender: personData.gender || null,
        address: personData.address || null,
        phone_number: personData.phone_number || null,
        place_sector_id: personData.place_sector_id || null,
    };

    if (reniecPhotoToken) {
        payload.reniec_photo_token = reniecPhotoToken;
    }

    const res = await http.post(`${BASE}/personas`, payload);
    return res.data?.data?.id ?? res.data?.id;
}

export default function PersonaInlineFields({
    value,
    onChange,
    options = {},
    title = 'Datos de la Persona',
}) {
    const toast = useToast();
    const isNew = value?.isNew ?? false;
    const personId = value?.personId ?? null;
    const personLabel = value?.personLabel ?? '';
    const personData = value?.personData ?? {
        dni: '',
        names: '',
        father_lastname: '',
        mother_lastname: '',
        birthdate: '',
        gender: '',
        address: '',
        phone_number: '',
        place_sector_id: '',
    };
    const [consultingReniec, setConsultingReniec] = useState(false);
    const [reniecStatus, setReniecStatus] = useState(null);

    const placeSectorOptions = (options.place_sectors ?? []).map((ps) => ({
        id: ps.id,
        label: [ps.place?.title, ps.sector?.title].filter(Boolean).join(' - '),
    }));

    const updateField = (field, val) => {
        const updated = { ...personData, [field]: val };
        onChange({
            ...value,
            isNew,
            personId,
            personLabel,
            personData: updated,
            reniecPhotoToken: value?.reniecPhotoToken ?? null,
        });
    };

    const handleDniChange = (val) => {
        const clean = val.replace(/\D/g, '').slice(0, 8);
        setReniecStatus(null);
        const updated = { ...personData, dni: clean };
        onChange({
            ...value,
            isNew,
            personId,
            personLabel,
            personData: updated,
            reniecPhotoToken: null,
        });
    };

    const handleReniecLookup = async () => {
        const dni = personData.dni;
        if (!/^\d{8}$/.test(dni)) {
            const msg = 'Ingrese un DNI válido de 8 dígitos.';
            setReniecStatus({ type: 'error', message: msg });
            toast.error(msg);
            return;
        }

        setConsultingReniec(true);
        setReniecStatus(null);

        try {
            const response = await http.post(`${BASE}/personas/reniec`, { dni }, { timeout: 22000 });
            const person = response.data.data;

            const updated = {
                ...personData,
                names: person.names || personData.names,
                father_lastname: person.father_lastname || personData.father_lastname,
                mother_lastname: person.mother_lastname || personData.mother_lastname,
                address: person.address || personData.address,
            };

            const restriction = person.restriction && person.restriction !== 'NINGUNA'
                ? ` Restricción: ${person.restriction}.`
                : '';
            const missingAddress = person.address ? '' : ' RENIEC no devolvió una dirección.';

            setReniecStatus({
                type: restriction || missingAddress ? 'warning' : 'success',
                message: `Datos obtenidos de RENIEC.${restriction}${missingAddress}`,
            });

            onChange({
                ...value,
                isNew: true,
                personData: updated,
                reniecPhotoToken: person.photo_token ?? null,
            });
        } catch (err) {
            const msg = errorMessage(err, 'No se pudo consultar RENIEC.');
            setReniecStatus({ type: 'error', message: msg });
            toast.error(msg);
        } finally {
            setConsultingReniec(false);
        }
    };

    return (
        <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4 space-y-3">
            <div className="border-b border-wheat/60 pb-2">
                <span className="text-xs font-bold text-charcoal flex items-center gap-1.5">
                    <i className="fas fa-id-card text-leaf" /> {title}
                </span>
            </div>

            <div className="space-y-3">
                    <div>
                        <label className={labelCls}>
                            DNI <span className="text-clay">*</span>
                        </label>
                        <div className="flex flex-col sm:flex-row gap-2">
                            <input
                                type="text"
                                inputMode="numeric"
                                maxLength={8}
                                value={personData.dni}
                                onChange={(e) => handleDniChange(e.target.value)}
                                placeholder="DNI de 8 dígitos"
                                className={`${inputCls} font-mono sm:flex-1`}
                            />
                            <button
                                type="button"
                                onClick={handleReniecLookup}
                                disabled={consultingReniec || personData.dni?.length !== 8}
                                className="btn-secondary min-h-[38px] px-3 text-xs font-bold disabled:opacity-50 shrink-0"
                            >
                                <i className={`fas ${consultingReniec ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'} mr-1.5`} />
                                {consultingReniec ? 'Consultando...' : 'Consultar RENIEC'}
                            </button>
                        </div>
                        {reniecStatus && (
                            <div
                                className={`mt-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold ${
                                    reniecStatus.type === 'success'
                                        ? 'bg-leaf-light text-leaf'
                                        : reniecStatus.type === 'warning'
                                        ? 'bg-sun-light text-amber-dark'
                                        : 'bg-clay-light text-clay'
                                }`}
                            >
                                {reniecStatus.message}
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label className={labelCls}>
                                Nombres <span className="text-clay">*</span>
                            </label>
                            <input
                                type="text"
                                value={personData.names}
                                onChange={(e) => updateField('names', e.target.value)}
                                className={inputCls}
                                required
                            />
                        </div>
                        <div>
                            <label className={labelCls}>
                                Apellido Paterno <span className="text-clay">*</span>
                            </label>
                            <input
                                type="text"
                                value={personData.father_lastname}
                                onChange={(e) => updateField('father_lastname', e.target.value)}
                                className={inputCls}
                                required
                            />
                        </div>
                        <div>
                            <label className={labelCls}>
                                Apellido Materno <span className="text-clay">*</span>
                            </label>
                            <input
                                type="text"
                                value={personData.mother_lastname}
                                onChange={(e) => updateField('mother_lastname', e.target.value)}
                                className={inputCls}
                                required
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label className={labelCls}>Fecha Nacimiento <Optional /></label>
                            <input
                                type="date"
                                value={personData.birthdate}
                                onChange={(e) => updateField('birthdate', e.target.value)}
                                className={inputCls}
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Sexo / Género <Optional /></label>
                            <select
                                value={personData.gender}
                                onChange={(e) => updateField('gender', e.target.value)}
                                className={inputCls}
                            >
                                <option value="">Seleccionar...</option>
                                <option value="F">Femenino</option>
                                <option value="M">Masculino</option>
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Celular <Optional /></label>
                            <input
                                type="text"
                                inputMode="numeric"
                                maxLength={9}
                                value={personData.phone_number}
                                onChange={(e) => updateField('phone_number', e.target.value.replace(/\D/g, ''))}
                                placeholder="9 dígitos"
                                className={inputCls}
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Barrio / Sector <Optional /></label>
                            <Combobox
                                value={personData.place_sector_id}
                                onChange={(id) => updateField('place_sector_id', id)}
                                options={placeSectorOptions}
                                placeholder="Seleccionar..."
                                allowClear
                            />
                        </div>
                    </div>

                    <div>
                        <label className={labelCls}>Dirección <Optional /></label>
                        <input
                            type="text"
                            value={personData.address}
                            onChange={(e) => updateField('address', e.target.value)}
                            placeholder="Av. / Calle / Jr. / Mz. y Lote"
                            className={inputCls}
                        />
                    </div>
            </div>
        </div>
    );
}
