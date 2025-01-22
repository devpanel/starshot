export const normalizeOptions = (value) => {
  const newValue = {};
  const isArray = Array.isArray(value);
  if (isArray) {
    Object.values(value).forEach((item) => {
      newValue[item.id] = item.name;
    });
  } else {
    Object.entries(value).forEach(([id, name]) => {
      newValue[id] = name;
    });
  }

  return newValue;
};

export const shallowCompare = (obj1, obj2) =>
  Object.keys(obj1).length === Object.keys(obj2).length &&
  Object.keys(obj1).every(
    (key) => obj2.hasOwnProperty(key) && obj1[key] === obj2[key],
  );

export const numberFormatter = new Intl.NumberFormat(navigator.language);

