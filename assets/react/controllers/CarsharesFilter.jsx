import React, { useState, useEffect } from "react";

const CarsharesFilter = (props) => {
  // Initialize with carshares from props
  const [carshares, setCarshares] = useState(props.carshares || []);
  const [filteredCarshares, setFilteredCarshares] = useState(
    props.carshares || []
  );
  const [filter, setFilter] = useState("");
  const [cityFilter, setCityFilter] = useState("");
  const [destinationFilter, setDestinationFilter] = useState("");
  const [energyFilter, setEnergyFilter] = useState("");

  // Component starts with carshares available, no loading needed
  const [loading, setLoading] = useState(false);

  // Update carshares state when props change
  useEffect(() => {
    setCarshares(props.carshares || []);
    setFilteredCarshares(props.carshares || []);
  }, [props.carshares]);

  // Apply filter when filter selection changes
  useEffect(() => {
    applyFilter();
  }, [filter, cityFilter, destinationFilter, energyFilter, carshares]);

  const applyFilter = () => {
    let sortedCarshares = [...carshares];

    // Apply city filter first
    if (cityFilter) {
      sortedCarshares = sortedCarshares.filter((carshare) =>
        carshare.startLocation.toLowerCase().includes(cityFilter.toLowerCase())
      );
    }

    // Apply destination filter
    if (destinationFilter) {
      sortedCarshares = sortedCarshares.filter((carshare) =>
        carshare.endLocation
          .toLowerCase()
          .includes(destinationFilter.toLowerCase())
      );
    }

    // Apply energy filter
    if (energyFilter) {
      sortedCarshares = sortedCarshares.filter(
        (carshare) =>
          carshare.car.energyType &&
          carshare.car.energyType.toLowerCase() === energyFilter.toLowerCase()
      );
    }

    // Apply sorting filter
    switch (filter) {
      case "price-asc":
        sortedCarshares.sort(
          (a, b) => parseFloat(a.price) - parseFloat(b.price)
        );
        break;
      case "price-desc":
        sortedCarshares.sort(
          (a, b) => parseFloat(b.price) - parseFloat(a.price)
        );
        break;
      case "start-location":
        sortedCarshares.sort((a, b) =>
          a.startLocation.localeCompare(b.startLocation)
        );
        break;
      case "end-location":
        sortedCarshares.sort((a, b) =>
          a.endLocation.localeCompare(b.endLocation)
        );
        break;
      case "status":
        sortedCarshares.sort((a, b) => a.status.localeCompare(b.status));
        break;
      case "start-date-asc":
        sortedCarshares.sort((a, b) => new Date(a.start) - new Date(b.start));
        break;
      case "start-date-desc":
        sortedCarshares.sort((a, b) => new Date(b.start) - new Date(a.start));
        break;
      case "end-date-asc":
        sortedCarshares.sort((a, b) => new Date(a.end) - new Date(b.end));
        break;
      case "end-date-desc":
        sortedCarshares.sort((a, b) => new Date(b.end) - new Date(a.end));
        break;
      default:
        break;
    }
    setFilteredCarshares(sortedCarshares);
  };

  const handleFilterChange = (event) => {
    setFilter(event.target.value);
  };

  const handleCityFilterChange = (event) => {
    setCityFilter(event.target.value);
  };

  const handleDestinationFilterChange = (event) => {
    setDestinationFilter(event.target.value);
  };

  const handleEnergyFilterChange = (event) => {
    setEnergyFilter(event.target.value);
  };

  // Get unique cities from carshares
  const getUniqueCities = () => {
    const cities = carshares.map((carshare) => carshare.startLocation);
    return [...new Set(cities)].sort();
  };

  // Get unique destination cities from carshares
  const getUniqueDestinations = () => {
    const destinations = carshares.map((carshare) => carshare.endLocation);
    return [...new Set(destinations)].sort();
  };

  // Get unique energy types from carshares
  const getUniqueEnergyTypes = () => {
    const energyTypes = carshares
      .filter((carshare) => carshare.car && carshare.car.energyType)
      .map((carshare) => carshare.car.energyType);
    return [...new Set(energyTypes)].sort();
  };

  return (
    <div className="w-100 d-flex align-items-center flex-column">
      {loading && (
        <div className="wrapper">
          <div className="rubik-loader"></div>
        </div>
      )}
      {!loading && (
        <>
          <h3 className="text-center w-100 mt-4">Filtres</h3>
          <div className="container-fluid py-4">
            <div className="row justify-content-center">
              <div className="col-md-3 col-sm-6 mb-3">
                <fieldset className="form-group">
                  <label htmlFor="filter" className="form-label">
                    Trier par
                  </label>
                  <select
                    id="filter"
                    value={filter}
                    onChange={handleFilterChange}
                    className="form-control"
                  >
                    <option value="">Sélectionner</option>
                    <option value="price-asc">Prix croissant</option>
                    <option value="price-desc">Prix décroissant</option>
                    <option value="start-location">Lieu de départ</option>
                    <option value="end-location">Lieu d'arrivée</option>
                    <option value="status">Statut</option>
                    <option value="start-date-asc">
                      Date de départ (plus ancien)
                    </option>
                    <option value="start-date-desc">
                      Date de départ (plus récent)
                    </option>
                    <option value="end-date-asc">
                      Date d'arrivée (plus ancien)
                    </option>
                    <option value="end-date-desc">
                      Date d'arrivée (plus récent)
                    </option>
                  </select>
                </fieldset>
              </div>

              <div className="col-md-3 col-sm-6 mb-3">
                <fieldset className="form-group">
                  <label htmlFor="cityFilter" className="form-label">
                    Ville de départ
                  </label>
                  <select
                    id="cityFilter"
                    value={cityFilter}
                    onChange={handleCityFilterChange}
                    className="form-control"
                  >
                    <option value="">Toutes les villes</option>
                    {getUniqueCities().map((city) => (
                      <option key={city} value={city}>
                        {city}
                      </option>
                    ))}
                  </select>
                </fieldset>
              </div>

              <div className="col-md-3 col-sm-6 mb-3">
                <fieldset className="form-group">
                  <label htmlFor="destinationFilter" className="form-label">
                    Ville d'arrivée
                  </label>
                  <select
                    id="destinationFilter"
                    value={destinationFilter}
                    onChange={handleDestinationFilterChange}
                    className="form-control"
                  >
                    <option value="">Toutes les villes</option>
                    {getUniqueDestinations().map((destination) => (
                      <option key={destination} value={destination}>
                        {destination}
                      </option>
                    ))}
                  </select>
                </fieldset>
              </div>

              <div className="col-md-3 col-sm-6 mb-3">
                <fieldset className="form-group">
                  <label
                    htmlFor="energyFilter"
                    className="form-label text-success"
                  >
                    <i className="fas fa-leaf me-2 text-success"></i>
                    Type d'énergie
                  </label>
                  <select
                    id="energyFilter"
                    value={energyFilter}
                    onChange={handleEnergyFilterChange}
                    className="form-control border-success"
                    style={{
                      borderColor: "#28a745",
                      boxShadow: energyFilter
                        ? "0 0 0 0.2rem rgba(40, 167, 69, 0.25)"
                        : "none",
                    }}
                  >
                    <option value="">Tous types</option>
                    {getUniqueEnergyTypes().map((energyType) => (
                      <option key={energyType} value={energyType}>
                        {energyType === "ELECTRICITE"
                          ? "⚡ Électrique"
                          : energyType === "ESSENCE"
                          ? "⛽ Essence"
                          : energyType === "GAZOIL"
                          ? "🛢️ Gasoil"
                          : energyType === "HYBRID"
                          ? "🔋 Hybride"
                          : energyType === "BIOCARBURANT"
                          ? "🌱 Biocarburant"
                          : energyType}
                      </option>
                    ))}
                  </select>
                </fieldset>
              </div>
            </div>
          </div>

          <div className="container gallery">
            {filteredCarshares.length === 0 ? (
              <div className="alert alert-info text-center w-75 mx-auto">
                <i className="fas fa-search mb-2"></i>
                <p className="mb-0">
                  Aucun covoiturage ne correspond à votre recherche.
                </p>
                <small className="text-muted">
                  Essayez de modifier vos critères de recherche.
                </small>
              </div>
            ) : (
              filteredCarshares.map((carshare) => (
                <div key={carshare.id} className="card mb-3">
                  <h3 className="card-head">
                    {carshare.startLocation} → {carshare.endLocation}
                  </h3>
                  <div className="card-body">
                    <div className="carshare-info">
                      <p>
                        <strong>Départ:</strong>{" "}
                        {new Date(carshare.start).toLocaleString()}
                      </p>
                      <p>
                        <strong>Arrivée:</strong>{" "}
                        {new Date(carshare.end).toLocaleString()}
                      </p>
                      <p>
                        <strong>Conducteur:</strong> {carshare.driver.firstname}{" "}
                        {carshare.driver.lastname}
                      </p>
                      <p>
                        <strong>Véhicule:</strong> {carshare.car.model} (
                        {carshare.car.color})
                      </p>
                      <p>
                        <strong>Statut:</strong> {carshare.status}
                      </p>
                      {carshare.place > 0 && (
                        <p>
                          <strong>Places disponibles:</strong> {carshare.place}
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="card-foot">
                    {carshare.price} euros
                    <a
                      href={`${props.carshareUrlPattern}${carshare.id}`}
                      className="btn-gallery"
                    >
                      Voir les détails
                    </a>
                  </div>
                </div>
              ))
            )}
          </div>
        </>
      )}
    </div>
  );
};

export default CarsharesFilter;
